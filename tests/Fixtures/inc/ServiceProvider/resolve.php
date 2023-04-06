<?php

use Fixtures\inc\classes\A;
use Fixtures\inc\classes\B;
use Fixtures\inc\classes\C;
use Fixtures\inc\classes\D;
use Fixtures\inc\classes\E;
use Fixtures\inc\classes\F;

return [
  'testValidTreeShouldLoad' => [
      'config' => [
          'load' => [
              LAUNCHPAD_AUTOLOADER_TESTS_FIXTURES_DIR . '/inc/classes/B.php',
              LAUNCHPAD_AUTOLOADER_TESTS_FIXTURES_DIR . '/inc/classes/C.php',
              LAUNCHPAD_AUTOLOADER_TESTS_FIXTURES_DIR . '/inc/classes/A.php',
          ],
          'classes' => [
                A::class,
          ],
          'bindings' => [],
          'parameters' => [
              'd' => 'test'
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
    'testValidTreeWithBindingShouldLoad' => [
        'config' => [
            'load' => [
                LAUNCHPAD_AUTOLOADER_TESTS_FIXTURES_DIR . '/inc/classes/B.php',
                LAUNCHPAD_AUTOLOADER_TESTS_FIXTURES_DIR . '/inc/classes/C.php',
                LAUNCHPAD_AUTOLOADER_TESTS_FIXTURES_DIR . '/inc/classes/A.php',
                LAUNCHPAD_AUTOLOADER_TESTS_FIXTURES_DIR . '/inc/classes/D.php',
                LAUNCHPAD_AUTOLOADER_TESTS_FIXTURES_DIR . '/inc/classes/E.php',
                LAUNCHPAD_AUTOLOADER_TESTS_FIXTURES_DIR . '/inc/classes/F.php',
            ],
            'classes' => [
                F::class,
            ],
            'bindings' => [
                [
                    'id' => D::class,
                    'concrete' => E::class,
                ]
            ],
            'parameters' => [
                'd' => 'test'
            ]
        ],
        'expected' => [
            'classes' => [
                D::class,
                B::class,
                C::class,
                A::class,
                F::class,
            ],
            'parameters' => [
                'd' => 'test'
            ]
        ]
    ],
];