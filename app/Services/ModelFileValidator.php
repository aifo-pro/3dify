<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

/**
 * Validates 3D model files for basic integrity and printability hints.
 *
 * Returns ['valid' => bool, 'warnings' => string[], 'errors' => string[]]
 */
class ModelFileValidator
{
    public function validate(UploadedFile $file, string $extension): array
    {
        $errors   = [];
        $warnings = [];

        $path = $file->getRealPath();
        if (! $path || ! is_readable($path)) {
            return ['valid' => false, 'errors' => ['File is not readable.'], 'warnings' => []];
        }

        $size = $file->getSize();
        if ($size === 0) {
            return ['valid' => false, 'errors' => ['File is empty.'], 'warnings' => []];
        }

        match (strtolower($extension)) {
            'stl'  => [$errors, $warnings] = $this->validateStl($path, $size),
            '3mf'  => [$errors, $warnings] = $this->validate3mf($path),
            'obj'  => [$errors, $warnings] = $this->validateObj($path),
            'glb'  => [$errors, $warnings] = $this->validateGlb($path),
            'gltf' => [$errors, $warnings] = $this->validateGltf($path),
            default => $warnings[] = "Format .{$extension} is accepted but cannot be validated automatically.",
        };

        return [
            'valid'    => empty($errors),
            'errors'   => $errors,
            'warnings' => $warnings,
        ];
    }

    // ─── STL ─────────────────────────────────────────────────────────────────

    private function validateStl(string $path, int $size): array
    {
        $errors = [];
        $warnings = [];

        $handle = fopen($path, 'rb');
        if (! $handle) {
            return [['Cannot open file for reading.'], []];
        }

        $header = fread($handle, 5);
        fclose($handle);

        if ($header === false) {
            return [['Cannot read file header.'], []];
        }

        $isAscii = stripos($header, 'solid') === 0;

        if ($isAscii) {
            [$errors, $warnings] = $this->validateAsciiStl($path);
        } else {
            [$errors, $warnings] = $this->validateBinaryStl($path, $size);
        }

        return [$errors, $warnings];
    }

    private function validateBinaryStl(string $path, int $size): array
    {
        $errors   = [];
        $warnings = [];

        // Binary STL: 80-byte header + 4-byte triangle count + N×50 bytes
        if ($size < 84) {
            $errors[] = 'Binary STL is too small to be valid (< 84 bytes).';
            return [$errors, $warnings];
        }

        $handle = fopen($path, 'rb');
        fread($handle, 80); // skip header
        $countData = fread($handle, 4);
        fclose($handle);

        if (strlen($countData) < 4) {
            $errors[] = 'Binary STL: cannot read triangle count.';
            return [$errors, $warnings];
        }

        $triangleCount = unpack('V', $countData)[1];

        if ($triangleCount === 0) {
            $errors[] = 'STL contains no triangles (empty geometry).';
            return [$errors, $warnings];
        }

        $expectedSize = 84 + ($triangleCount * 50);
        if (abs($size - $expectedSize) > 4) {
            $warnings[] = "STL triangle count ({$triangleCount}) does not match file size — file may be truncated or corrupt.";
        }

        if ($triangleCount > 5_000_000) {
            $warnings[] = "Very high triangle count ({$triangleCount}). Slicer may struggle with this model.";
        }

        return [$errors, $warnings];
    }

    private function validateAsciiStl(string $path): array
    {
        $errors   = [];
        $warnings = [];

        $content = file_get_contents($path, false, null, 0, 65536); // read first 64 KB
        if ($content === false) {
            $errors[] = 'Cannot read ASCII STL content.';
            return [$errors, $warnings];
        }

        if (! preg_match('/facet\s+normal/i', $content)) {
            $errors[] = 'ASCII STL has no facets — file may be empty or corrupt.';
        }

        if (substr_count($content, 'endsolid') === 0 && strlen($content) < 65536) {
            $warnings[] = 'ASCII STL may be missing "endsolid" terminator.';
        }

        return [$errors, $warnings];
    }

    // ─── 3MF ─────────────────────────────────────────────────────────────────

    private function validate3mf(string $path): array
    {
        $errors   = [];
        $warnings = [];

        // 3MF is a ZIP archive
        if (! extension_loaded('zip')) {
            $warnings[] = '3MF validation skipped — ZIP extension not available.';
            return [$errors, $warnings];
        }

        $zip = new \ZipArchive;
        $result = $zip->open($path);

        if ($result !== true) {
            $errors[] = '3MF file is not a valid ZIP archive (error code '.$result.').';
            return [$errors, $warnings];
        }

        $hasContentTypes = $zip->locateName('[Content_Types].xml') !== false;
        $has3dModel      = $zip->locateName('3D/3dmodel.model') !== false;

        $zip->close();

        if (! $hasContentTypes) {
            $errors[] = '3MF is missing [Content_Types].xml — not a valid 3MF package.';
        }

        if (! $has3dModel) {
            $errors[] = '3MF is missing 3D/3dmodel.model — no geometry found.';
        }

        return [$errors, $warnings];
    }

    // ─── OBJ ─────────────────────────────────────────────────────────────────

    private function validateObj(string $path): array
    {
        $errors   = [];
        $warnings = [];

        $content = file_get_contents($path, false, null, 0, 32768);
        if ($content === false) {
            $errors[] = 'Cannot read OBJ file.';
            return [$errors, $warnings];
        }

        $hasVertices = preg_match('/^v\s/m', $content);
        $hasFaces    = preg_match('/^f\s/m', $content);

        if (! $hasVertices) {
            $errors[] = 'OBJ has no vertices — file may be empty or corrupt.';
        }

        if (! $hasFaces) {
            $warnings[] = 'OBJ has no face definitions — model may not be printable.';
        }

        return [$errors, $warnings];
    }

    // ─── GLB ─────────────────────────────────────────────────────────────────

    private function validateGlb(string $path): array
    {
        $errors   = [];
        $warnings = [];

        $handle = fopen($path, 'rb');
        if (! $handle) {
            return [['Cannot open GLB file.'], []];
        }

        $magic = fread($handle, 4);
        fclose($handle);

        // GLB magic: 0x46546C67 ("glTF")
        if ($magic !== "glTF") {
            $errors[] = 'GLB magic bytes missing — not a valid GLB file.';
        }

        return [$errors, $warnings];
    }

    // ─── GLTF ────────────────────────────────────────────────────────────────

    private function validateGltf(string $path): array
    {
        $errors   = [];
        $warnings = [];

        $content = file_get_contents($path, false, null, 0, 4096);
        if ($content === false) {
            $errors[] = 'Cannot read GLTF file.';
            return [$errors, $warnings];
        }

        $json = json_decode($content, true);
        if ($json === null) {
            $errors[] = 'GLTF is not valid JSON.';
            return [$errors, $warnings];
        }

        if (empty($json['asset']['version'])) {
            $warnings[] = 'GLTF is missing asset.version field.';
        }

        if (empty($json['meshes'])) {
            $warnings[] = 'GLTF has no meshes — model may be empty.';
        }

        return [$errors, $warnings];
    }
}
