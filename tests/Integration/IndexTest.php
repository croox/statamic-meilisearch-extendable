<?php

namespace Croox\StatamicMeilisearchExtendable\Tests\Integration;

use Statamic\Facades;

class IndexTest extends IntegrationTestCase
{
    public function testAddsDocumentsToTheIndex(): void
    {
        $collection = Facades\Collection::make()
            ->handle('pages')
            ->title('Pages')
            ->save();

        $entry1 = Facades\Entry::make()
            ->id('test-2')
            ->collection('pages')
            ->data(['title' => 'Entry 1'])
            ->save();

        $entry2 = Facades\Entry::make()
            ->id('test-1')
            ->collection('pages')
            ->data(['title' => 'Entry 2'])
            ->save();

        sleep(1); // give meili some time to process

        $result = $this->getIndex()->search('Entry')->getSearchResults();
        $this->assertCount(2, $result);
    }

    public function testUpdatesDocumentsInIndex(): void
    {
        $collection = Facades\Collection::make()
            ->handle('pages')
            ->title('Pages')
            ->save();

        $entry1 = Facades\Entry::make()
            ->id('test-2')
            ->collection('pages')
            ->data(['title' => 'Entry 1'])
            ->save();

        $entry2 = tap(Facades\Entry::make()
            ->id('test-1')
            ->collection('pages')
            ->data(['title' => 'Entry 2']))
            ->save();

        sleep(1); // give meili some time to process

        $index = $this->getIndex();

        $results = $index->search('Entry')->getSearchResults()->pluck('title');

        $this->assertContains('Entry 1', $results);
        $this->assertContains('Entry 2', $results);

        $entry2->merge(['title' => 'Entry 2 Updated'])->save();

        sleep(1); // give meili some time to process

        $results = $index->search('Entry')->getSearchResults()->pluck('title');
        $this->assertContains('Entry 2 Updated', $results);
    }

    public function testRemovesDocumentsFromTheIndex(): void
    {
        $collection = Facades\Collection::make()
            ->handle('pages')
            ->title('Pages')
            ->save();

        $entry1 = Facades\Entry::make()
            ->id('test-2')
            ->collection('pages')
            ->data(['title' => 'Entry 1'])
            ->save();

        $entry2 = tap(Facades\Entry::make()
            ->id('test-1')
            ->collection('pages')
            ->data(['title' => 'Entry 2']))
            ->save();

        $entry2->delete();

        $index = $this->getIndex();

        sleep(1); // give meili some time to process

        $results = $index->search('Entry')->getSearchResults();
        $this->assertCount(1, $results);
    }
}
