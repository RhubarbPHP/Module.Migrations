<?php

include __DIR__ . '/../../vendor/rhubarbphp/rhubarb/platform/execute-test.php';

// The application has an array of objects which contain closures (authentication url handlers). Codeception tries to
// seriialise everything in the global state before each test. Closures cannot be serialised apparently. Removing the
// application from the global state fixes this issue.
unset($application);