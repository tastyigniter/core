<?php

namespace Igniter\Tests\System\DashboardWidgets;

use DOMDocument;
use Igniter\Admin\Http\Controllers\Dashboard;
use Igniter\System\DashboardWidgets\News;

beforeEach(function() {
    $doc = createRssFeed();
    app()->instance(DOMDocument::class, $this->dom = mock(DOMDocument::class));
    $this->dom->shouldReceive('load')->andReturn(false)->byDefault();
    $this->dom->shouldReceive('getElementsByTagName')->andReturn($doc->getElementsByTagName('entry'));
    $this->newsWidget = new News(resolve(Dashboard::class), []);
});

it('renders news widget successfully', function() {
    expect($this->newsWidget->render())->toBeString()
        ->and($this->newsWidget->vars['newsFeed'])->toBeArray()
        ->and(count($this->newsWidget->vars['newsFeed']))->toBeLessThanOrEqual(6);
});

it('defines widget properties correctly', function() {
    $properties = $this->newsWidget->defineProperties();

    expect($properties)->toBe([
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
    ]);
});

it('handles invalid RSS feed URL', function() {
    $this->dom->shouldReceive('load')->andThrow(new \Exception('Error'));
    $this->newsWidget->newsRss = 'https://invalid-url.com/feed';

    $this->newsWidget->render();

    expect($this->newsWidget->vars['newsFeed'])->toBe([]);
});

function createRssFeed()
{
    $doc = new DOMDocument('1.0', 'UTF-8');
    $doc->formatOutput = true;

    $feed = $doc->createElement('feed');
    $feed->setAttribute('xmlns', 'http://www.w3.org/2005/Atom');
    $doc->appendChild($feed);
    $title = $doc->createElement('title', 'My Blog Feed');
    $feed->appendChild($title);
    $link = $doc->createElement('link');
    $link->setAttribute('href', 'https://example.com/blog');
    $link->setAttribute('rel', 'self');
    $feed->appendChild($link);
    $updated = $doc->createElement('updated', date(DATE_ATOM));
    $feed->appendChild($updated);
    $entry = $doc->createElement('entry');
    $entryTitle = $doc->createElement('title', 'First Blog Post');
    $entry->appendChild($entryTitle);
    $entryLink = $doc->createElement('link');
    $entryLink->setAttribute('href', 'https://example.com/blog/first-post');
    $entry->appendChild($entryLink);
    $entryId = $doc->createElement('id', 'https://example.com/blog/first-post');
    $entry->appendChild($entryId);
    $entryUpdated = $doc->createElement('updated', date(DATE_ATOM, strtotime('2025-02-13T10:00:00Z')));
    $entry->appendChild($entryUpdated);
    $entrySummary = $doc->createElement('summary', 'This is a summary of the first blog post.');
    $entry->appendChild($entrySummary);
    $feed->appendChild($entry);

    return $doc;
}
