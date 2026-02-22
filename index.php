<?php

require "duplicate_finder.php";

$finder = new ImageDuplicateFinder();

$duplicates = $finder->findDuplicates("images", 0.92);

foreach ($duplicates as $dup) {
    echo "Duplicate found:\n";
    echo $dup['image1'] . "\n";
    echo $dup['image2'] . "\n";
    echo "Similarity: " . $dup['similarity'] . "\n\n";
}