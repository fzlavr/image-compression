<?php
// === Resize Function (using GD) ===


function fixImageOrientation($filename, &$image) {
    if (function_exists('exif_read_data')) {
        $exif = @exif_read_data($filename);
        if (!empty($exif['Orientation'])) {
            switch ($exif['Orientation']) {
                case 3:
                    $image = imagerotate($image, 180, 0);
                    break;
                case 6:
                    $image = imagerotate($image, -90, 0);
                    break;
                case 8:
                    $image = imagerotate($image, 90, 0);
                    break;
            }
        }
    }
}

function resizeImage($sourcePath, $targetPath, $maxWidth, $maxHeight) {
    // Get image type only (not using width/height yet)
    $imageType = exif_imagetype($sourcePath);

    switch ($imageType) {
        case IMAGETYPE_JPEG:
            $image = imagecreatefromjpeg($sourcePath);
            break;
        case IMAGETYPE_PNG:
            $image = imagecreatefrompng($sourcePath);
            break;
        case IMAGETYPE_GIF:
            $image = imagecreatefromgif($sourcePath);
            break;
        default:
            die("Unsupported image type.");
    }

    // Fix orientation before resizing
    if ($imageType == IMAGETYPE_JPEG) {
        fixImageOrientation($sourcePath, $image);
    }

    // Now get actual width & height (after rotation)
    $origWidth = imagesx($image);
    $origHeight = imagesy($image);

    // Keep aspect ratio
    $ratio = min($maxWidth / $origWidth, $maxHeight / $origHeight);
    $newWidth = (int)($origWidth * $ratio);
    $newHeight = (int)($origHeight * $ratio);

    $newImage = imagecreatetruecolor($newWidth, $newHeight);

    if ($imageType == IMAGETYPE_PNG || $imageType == IMAGETYPE_GIF) {
        imagecolortransparent(
            $newImage,
            imagecolorallocatealpha($newImage, 0, 0, 0, 127)
        );
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
    }

    imagecopyresampled(
        $newImage, $image, 0, 0, 0, 0,
        $newWidth, $newHeight, $origWidth, $origHeight
    );

    switch ($imageType) {
        case IMAGETYPE_JPEG:
            imagejpeg($newImage, $targetPath, 90);
            break;
        case IMAGETYPE_PNG:
            imagepng($newImage, $targetPath, 6);
            break;
        case IMAGETYPE_GIF:
            imagegif($newImage, $targetPath);
            break;
    }

    imagedestroy($image);
    imagedestroy($newImage);
}


// === Handle Upload ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $uploadDir = __DIR__ . "/uploads/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $tmpName = $_FILES['image']['tmp_name'];
    $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    // $fileName = uniqid("img_", true) . "." . strtolower($ext);
    $fileName = time() . "_" . uniqid() . "." . strtolower($ext);
    $targetPath = $uploadDir . $fileName;

    // $fileName = basename($_FILES['image']['name']);
    // $targetPath = $uploadDir . "resized_" . $fileName;

    resizeImage($tmpName, $targetPath, 800, 800); // max size 800x800

    echo "<p>Image uploaded & resized successfully!</p>";
    echo "<p>Saved to: uploads/$fileName</p>";
    echo "<img src='uploads/$fileName' style='max-width:400px;'><br>";
}
?>

<!-- === Upload Form ===
<!DOCTYPE html>
<html>
<head>
    <title>PHP GD Image Resize Demo</title>
</head>
<body>
    <h2>Upload an Image (JPEG, PNG, GIF)</h2>
    <form method="post" enctype="multipart/form-data">
        <input type="file" id="take-picture" accept="image/*" capture="camera" name="image" required>
        <button type="submit">Upload & Resize</button>
    </form>
</body>
</html> -->

<!DOCTYPE html>
<html>
<head>
    <title>Image Upload with Preview</title>
    <style>
        .preview-container {
            margin-top: 10px;
            display: none;
        }
        .preview-container img {
            max-width: 300px;
            border: 1px solid #ccc;
            border-radius: 8px;
        }
        .remove-btn {
            display: inline-block;
            margin-top: 5px;
            padding: 5px 10px;
            background: #e74c3c;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .remove-btn:hover {
            background: #c0392b;
        }
    </style>
    
</head>
<body>
    <h2>Upload an Image (JPEG, PNG, GIF)</h2>
    <form method="post" enctype="multipart/form-data">
        <input type="file" name="image" id="imageInput" accept="image/*" required>
        <div class="preview-container" id="previewContainer">
            <p>Preview:</p>
            <img id="previewImage" src="">
            <br>
            <button type="button" class="remove-btn" id="removePreview">Remove</button>
        </div>
        <br>
        <button type="submit" id="uploadBtn" hidden>Upload & Resize</button>
    </form>
    <br>
    <br>

    <script>
        const imageInput = document.getElementById('imageInput');
        const previewContainer = document.getElementById('previewContainer');
        const previewImage = document.getElementById('previewImage');
        const removePreview = document.getElementById('removePreview');
        const uploadBtn = document.getElementById('uploadBtn');

        // Show preview when file selected
        imageInput.addEventListener('change', function () {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    previewImage.src = e.target.result;
                    previewContainer.style.display = 'block';
                    uploadBtn.style.display = 'inline-block';
                    uploadBtn.disabled = false;
                };
                reader.readAsDataURL(file);
            } else {
                resetPreview();
            }
        });

        // Remove preview
        removePreview.addEventListener('click', function () {
            resetPreview();
        });

        function resetPreview() {
            previewImage.src = '';
            previewContainer.style.display = 'none';
            imageInput.value = ''; // Clear file input
            uploadBtn.style.display = 'none';
            uploadBtn.disabled = true;
        }
    </script>
    
</body>

</html>
