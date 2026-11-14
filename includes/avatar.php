<?php
// Profile pictures: validate, store under uploads/avatars/ with a random name, update the user.
require_once __DIR__ . '/auth.php';

const AVATAR_DIR      = 'uploads/avatars';
const AVATAR_MAX_SIZE = 2 * 1024 * 1024;   // 2 MB
const AVATAR_TYPES    = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];

/** Save an uploaded avatar for $userId. Returns an error message (English) or null on success. */
function avatar_save(int $userId, ?array $file): ?string
{
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return 'Choose an image to upload.';
    }
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        return 'The upload failed. Please try again.';
    }
    if ($file['size'] > AVATAR_MAX_SIZE) {
        return 'Images can be at most 2 MB.';
    }

    // Trust the file contents, never the name or browser-sent type
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $info = @getimagesize($file['tmp_name']);
    if (!isset(AVATAR_TYPES[$mime]) || !$info || $info[0] < 32 || $info[1] < 32 || $info[0] > 5000 || $info[1] > 5000) {
        return 'Use a JPG, PNG, WebP or GIF image (at least 32 × 32 pixels).';
    }

    $dir = __DIR__ . '/../' . AVATAR_DIR;
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        return 'The upload failed. Please try again.';
    }

    $name = bin2hex(random_bytes(16));
    $saved = avatar_resize($file['tmp_name'], $mime, "$dir/$name");   // square 256px if GD is available
    if ($saved === null) {
        $saved = "$name." . AVATAR_TYPES[$mime];
        if (!move_uploaded_file($file['tmp_name'], "$dir/$saved")) {
            return 'The upload failed. Please try again.';
        }
    }

    avatar_delete_file($userId);
    db()->prepare('UPDATE users SET avatar_path = ? WHERE id = ?')->execute([AVATAR_DIR . '/' . $saved, $userId]);
    return null;
}

/** Remove the user's current avatar (file + database value). */
function avatar_remove(int $userId): void
{
    avatar_delete_file($userId);
    db()->prepare('UPDATE users SET avatar_path = NULL WHERE id = ?')->execute([$userId]);
}

function avatar_delete_file(int $userId): void
{
    $stmt = db()->prepare('SELECT avatar_path FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $path = (string) $stmt->fetchColumn();
    // Only ever delete our own random-named files
    if (preg_match('#^' . AVATAR_DIR . '/[a-f0-9]{32}\.(jpg|png|webp|gif)$#', $path)) {
        @unlink(__DIR__ . '/../' . $path);
    }
}

/** Centre-crop to a 256px square PNG when the GD extension is enabled. Returns the file name or null. */
function avatar_resize(string $src, string $mime, string $destNoExt): ?string
{
    if (!extension_loaded('gd')) return null;
    $img = match ($mime) {
        'image/jpeg' => @imagecreatefromjpeg($src),
        'image/png'  => @imagecreatefrompng($src),
        'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($src) : false,
        'image/gif'  => @imagecreatefromgif($src),
        default      => false,
    };
    if (!$img) return null;
    $w = imagesx($img);
    $h = imagesy($img);
    $side = min($w, $h);
    $out = imagecreatetruecolor(256, 256);
    imagealphablending($out, false);
    imagesavealpha($out, true);
    imagecopyresampled($out, $img, 0, 0, (int) (($w - $side) / 2), (int) (($h - $side) / 2), 256, 256, $side, $side);
    $ok = imagepng($out, "$destNoExt.png");
    imagedestroy($img);
    imagedestroy($out);
    return $ok ? basename($destNoExt) . '.png' : null;
}
