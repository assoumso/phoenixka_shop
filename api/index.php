<?php
/**
 * Vercel Serverless PHP Entry Point
 */
chdir(dirname(__DIR__));
require __DIR__ . '/../router.php';
