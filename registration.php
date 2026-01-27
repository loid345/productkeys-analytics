<?php

use Magento\Framework\Component\ComponentRegistrar;

if (!class_exists(ComponentRegistrar::class)) {
    return;
}

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Dart_ProductkeysAnalytics',
    __DIR__
);
