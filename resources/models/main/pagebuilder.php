<?php

/**
 * The theme page builder: one page builder field that lists the theme's pages
 * beside the sections. The theme and page come from the ThemePage model.
 */
return [
    'form' => [
        'fields' => [
            'sections' => [
                'type' => 'pagebuilder',
                'showPages' => true,
                'pageUrl' => 'themes/builder/{theme}/{page}',
            ],
        ],
    ],
];
