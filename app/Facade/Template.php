<?php
/**
 * Created by PhpStorm.
 * User: mostafa
 * Date: 7/20/19
 * Time: 12:47 PM
 */

namespace App\Facade;

use Symfony\Component\Templating\Loader\FilesystemLoader;
use Symfony\Component\Templating\PhpEngine;
use Symfony\Component\Templating\TemplateNameParser;

class Template
{
    public static function render($name, array $parameters = [])
    {
        $filesystemLoader = new FilesystemLoader(__DIR__ . '/../Views/%name%');
        $templating = new PhpEngine(new TemplateNameParser(), $filesystemLoader);
        return $templating->render($name, $parameters);
    }
}