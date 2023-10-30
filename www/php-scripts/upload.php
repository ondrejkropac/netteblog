<?php

//$file = $upload_destination . md5(uniqid()) . '.' . end(explode('.', $_FILES['files']['name'][$i]));

// Make sure the captured data exists
if (isset($_FILES['picture']) && !empty($_FILES['picture'])) {
    // Upload destination directory
    //$upload_destination = 'C:\xampp\htdocs\upload/';
    $upload_destination = 'C:\xampp\htdocs/www\images\gallery/';
    // Iterate all the files and move the temporary file to the new directory
    /*for ($i = 0; $i < count($_FILES['picture']['tmp_name']); $i++) {
        // Add your validation here
        $file = $upload_destination . $_FILES['picture']['name'][$i];print_r($file);
        
        // Move temporary files to new specified location
        move_uploaded_file($_FILES['picture']['tmp_name'][$i], $file);
    }*/
    // Add your validation here
    //$file = $upload_destination . $_FILES['picture']['name'];print_r($file);
    $file = $upload_destination. $_POST['imgFullNameGalerie'];
    if ((file_exists($file))) $file = $upload_destination. uniqid() . '_' . $_POST['imgFullNameGalerie']; // . '.jpg'
    print_r($file);
    // Move temporary files to new specified location
    move_uploaded_file($_FILES['picture']['tmp_name'], $file);
    // Output response
    echo 'Upload Complete!';
}

        //buď takto napřímo a nebo includovat nebo dependent injection
        
        /*$pic = $values['picture'];
        if (!empty($pic) && $pic->isOk()) {
            $im = $pic->toImage();
            $im->resize(900, 400, Image::EXACT);
            $im->save('images/image_' . $clanek->clanky_id . '.jpg', 90, Image::JPEG); // cestu $this->picturePath dočasně nastavuju na images*/

?>
        <!--
        if (getimagesize($_FILES['files']['tmp_name'][$i]) === FALSE) {
            exit('Unsupported format! Please upload an image file!');
        }

        if ($_FILES['files']['size'][$i] > 200000) {
            exit('Please upload a file less than 200KB!');
        }

        if (!preg_match('/video\/*/', $_FILES['files']['type'][$i])) {
            exit('Unsupported format! Please upload an video file!');
        }