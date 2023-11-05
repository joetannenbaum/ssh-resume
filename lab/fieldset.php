<?php

use ChewieLab\Fieldset;
use Laravel\Prompts\TextPrompt;

require __DIR__ . '/../vendor/autoload.php';

(new Fieldset(
    new TextPrompt('Name', 'Your Name'),
    new TextPrompt('Email', 'Your Email'),
    new TextPrompt('Phone', 'Your Phone'),
))->prompt();
