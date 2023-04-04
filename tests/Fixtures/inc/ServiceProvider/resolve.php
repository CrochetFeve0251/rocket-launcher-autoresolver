<?php

use Fixtures\inc\classes\A;
use Fixtures\inc\classes\B;
use Fixtures\inc\classes\C;

return [
  'testValidTreeShouldLoad' => [
      'config' => [
          'load' => [
              ROCKET_LAUNCHER_AUTOLOADER_TESTS_FIXTURES_DIR . '/inc/classes/B.php',
              ROCKET_LAUNCHER_AUTOLOADER_TESTS_FIXTURES_DIR . '/inc/classes/C.php',
              ROCKET_LAUNCHER_AUTOLOADER_TESTS_FIXTURES_DIR . '/inc/classes/A.php',
          ],
          'classes' => [
                A::class,
          ]
      ],
      'expected' => [
            'classes' => [
                B::class,
                C::class,
                A::class,
            ],
            'parameters' => [
                'd' => 'test'
            ]
      ]
  ],
];