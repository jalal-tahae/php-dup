<?php

class ImageDuplicateFinder
{
    private $bins = 16; // histogram bins per channel

    // Load image safely
    private function loadImage($path)
    {
        $info = getimagesize($path);

        switch ($info[2]) {
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($path);
            case IMAGETYPE_PNG:
                return imagecreatefrompng($path);
            case IMAGETYPE_GIF:
                return imagecreatefromgif($path);
            default:
                return false;
        }
    }

    // Generate histogram
    public function getHistogram($path)
    {
        $img = $this->loadImage($path);
        if (!$img) return false;

        $width = imagesx($img);
        $height = imagesy($img);

        $histogram = array_fill(0, $this->bins * 3, 0);

        for ($x = 0; $x < $width; $x += 2) {
            for ($y = 0; $y < $height; $y += 2) {

                $rgb = imagecolorat($img, $x, $y);

                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                $rBin = floor($r / (256 / $this->bins));
                $gBin = floor($g / (256 / $this->bins));
                $bBin = floor($b / (256 / $this->bins));

                $histogram[$rBin]++;
                $histogram[$this->bins + $gBin]++;
                $histogram[$this->bins*2 + $bBin]++;
            }
        }

        imagedestroy($img);

        return $this->normalize($histogram);
    }

    // Normalize histogram
    private function normalize($hist)
    {
        $sum = array_sum($hist);
        if ($sum == 0) return $hist;

        foreach ($hist as &$value)
            $value /= $sum;

        return $hist;
    }

    // Compare histograms
    public function compare($hist1, $hist2)
    {
        $diff = 0;

        for ($i = 0; $i < count($hist1); $i++)
        {
            $diff += abs($hist1[$i] - $hist2[$i]);
        }

        return 1 - ($diff / 2);
    }

    // Find duplicates in folder
    public function findDuplicates($folder, $threshold = 0.90)
    {
        $files = glob($folder . "/*.{jpg,jpeg,png,gif}", GLOB_BRACE);

        $histograms = [];

        foreach ($files as $file)
        {
            $histograms[$file] = $this->getHistogram($file);
        }

        $duplicates = [];

        foreach ($histograms as $file1 => $hist1)
        {
            foreach ($histograms as $file2 => $hist2)
            {
                if ($file1 >= $file2) continue;

                $similarity = $this->compare($hist1, $hist2);

                if ($similarity >= $threshold)
                {
                    $duplicates[] = [
                        'image1' => $file1,
                        'image2' => $file2,
                        'similarity' => round($similarity, 3)
                    ];
                }
            }
        }

        return $duplicates;
    }
}