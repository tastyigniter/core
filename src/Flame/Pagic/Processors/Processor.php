<?php

namespace Igniter\Flame\Pagic\Processors;

use Igniter\Flame\Pagic\Finder;
use Igniter\Flame\Pagic\Parsers\SectionParser;

class Processor
{
    /**
     * Process the results of a singular "select" query.
     *
     * @param array $result
     *
     * @return array
     */
    public function processSelect(Finder $finder, $result)
    {
        if ($result === null) {
            return null;
        }

        $fileName = array_get($result, 'fileName');

        return [$fileName => $this->parseTemplateContent($result, $fileName, $finder)];
    }

    /**
     * Process the results of a "select" query.
     *
     * @param array $results
     *
     * @return array
     */
    public function processSelectAll(Finder $finder, $results)
    {
        if (!count($results)) {
            return [];
        }

        $items = [];

        foreach ($results as $result) {
            $fileName = array_get($result, 'fileName');
            $items[$fileName] = $this->parseTemplateContent($result, $fileName, $finder);
        }

        return $items;
    }

    /**
     * Helper to break down template content in to a useful array.
     *
     * @return array
     */
    protected function parseTemplateContent($result, $fileName, Finder $finder)
    {
        $content = array_get($result, 'content');

        $processed = SectionParser::parse($content);

        return [
            'fileName' => $fileName,
            'mTime' => array_get($result, 'mTime'),
            'content' => $content,
            'markup' => $processed['markup'],
            'code' => $processed['code'],
            'settings' => $processed['settings'],
        ];
    }

    /**
     * Process the data in to an insert action.
     *
     * @param array $data
     *
     * @return string
     */
    public function processInsert(Finder $finder, $data)
    {
        return SectionParser::render($data);
    }

    /**
     * Process the data in to an update action.
     *
     * @param array $data
     *
     * @return string
     */
    public function processUpdate(Finder $finder, $data)
    {
        $existingData = $finder->getModel()->attributesToArray();

        return SectionParser::render($data + $existingData);
    }
}
