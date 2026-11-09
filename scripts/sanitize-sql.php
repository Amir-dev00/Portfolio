<?php

$root = dirname(__DIR__);
$rawPath = $root.DIRECTORY_SEPARATOR.'portfolio-final.raw.sql';
$outPath = $root.DIRECTORY_SEPARATOR.'portfolio-final.sql';

if (! is_file($rawPath)) {
    fwrite(STDERR, "Missing raw SQL dump\n");
    exit(1);
}

$raw = file_get_contents($rawPath);
$raw = preg_replace('/^CREATE DATABASE.*\r?\n/m', '', $raw);
$raw = preg_replace('/^USE .*?\r?\n/m', '', $raw);
$raw = preg_replace('/DEFINER=`[^`]+`@`[^`]+`/', '', $raw);
$raw = preg_replace('/^INSERT INTO `users`.*?;\r?\n/ms', '', $raw);

$header = <<<SQL
-- AZ Portfolio CMS
-- Import into an empty database via phpMyAdmin.
-- Character set: utf8mb4
-- No CREATE DATABASE / no DEFINER / no default admin password.
-- Create the first owner with tools/make-owner-hash.php (see INSTALL-CPANEL.md).

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

SQL;

file_put_contents($outPath, $header.$raw."\nSET FOREIGN_KEY_CHECKS=1;\n");
echo 'Wrote '.$outPath.' ('.filesize($outPath)." bytes)\n";
