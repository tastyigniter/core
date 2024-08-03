<?php

namespace Igniter\System\DashboardWidgets;

use DOMDocument;
use Igniter\Admin\Classes\BaseDashboardWidget;

/**
 * TastyIgniter news dashboard widget.
 */
class News extends BaseDashboardWidget
{
    /**
     * @var string A unique alias to identify this widget.
     */
    protected string $defaultAlias = 'news';

    public string $newsRss = 'https://tastyigniter.com/feed';

    public function render()
    {
        $this->prepareVars();

        return $this->makePartial('news/news');
    }

    public function defineProperties(): array
    {
        return [
            'title' => [
                'label' => 'igniter::admin.dashboard.label_widget_title',
                'default' => 'igniter::admin.dashboard.text_news',
            ],
            'newsCount' => [
                'label' => 'igniter::admin.dashboard.text_news_count',
                'default' => 6,
                'type' => 'select',
                'options' => range(1, 10),
                'validationRule' => 'required|integer',
            ],
        ];
    }

    protected function prepareVars()
    {
        $this->vars['newsFeed'] = $this->loadFeedItems();
    }

    public function loadFeedItems(): array
    {
        $dom = $this->createRssDocument();
        if (!$dom || !$dom->load($this->newsRss)) {
            return [];
        }

        $newsFeed = [];
        foreach ($dom->getElementsByTagName('entry') as $content) {
            $newsFeed[] = [
                'title' => $content->getElementsByTagName('title')->item(0)->nodeValue,
                'description' => $content->getElementsByTagName('summary')->item(0)->nodeValue,
                'link' => $content->getElementsByTagName('link')->item(0)->getAttribute('href'),
                'date' => $content->getElementsByTagName('updated')->item(0)->nodeValue,
            ];
        }

        $newsCount = $this->property('newsCount');
        $count = (($count = count($newsFeed)) < $newsCount) ? $count : $newsCount;

        return array_slice($newsFeed, 0, $count);
    }

    public function createRssDocument(): ?DOMDocument
    {
        return class_exists('DOMDocument', false) ? new DOMDocument : null;
    }
}
