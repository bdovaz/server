<?php

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\ObjectStore;

use OC\Files\Cache\Scanner;
use OC\Files\ObjectStore\ObjectStoreScanner;
use OC\Files\Storage\Temporary;
use OCP\Files\Cache\ICache;
use OCP\Files\Storage\IStorage;
use Test\TestCase;

/**
 * @group DB
 */
class ObjectStoreScannerTest extends TestCase {
	private IStorage $storage;
	private ICache $cache;
	private ObjectStoreScanner $scanner;
	private Scanner $realScanner;

	protected function setUp(): void {
		parent::setUp();

		$this->storage = new Temporary([]);
		$this->cache = $this->storage->getCache();
		$this->scanner = new ObjectStoreScanner($this->storage);
		$this->realScanner = new Scanner($this->storage);
	}

	public function testFile(): void {
		$data = "dummy file data\n";
		$this->storage->file_put_contents('foo.txt', $data);

		$this->assertNull($this->scanner->scanFile('foo.txt'),
			'Asserting that no error occurred while scanFile()'
		);
	}

	private function fillTestFolders() {
		$textData = "dummy file data\n";
		$imgData = file_get_contents(\OC::$SERVERROOT . '/core/img/logo/logo.png');
		$this->storage->mkdir('folder');
		$this->storage->file_put_contents('foo.txt', $textData);
		$this->storage->file_put_contents('foo.png', $imgData);
		$this->storage->file_put_contents('folder/bar.txt', $textData);
	}

	public function testFolder(): void {
		$this->fillTestFolders();

		$this->assertNull(
			$this->scanner->scan(''),
			'Asserting that no error occurred while scan()'
		);
	}

	public function testBackgroundScan(): void {
		$this->fillTestFolders();
		$this->storage->mkdir('folder2');
		$this->storage->file_put_contents('folder2/bar.txt', 'foobar');

		$this->realScanner->scan('');

		$this->assertEquals(6, $this->cache->get('folder2')->getSize());

		$this->cache->put('folder2', ['size' => -1]);

		$this->assertEquals(-1, $this->cache->get('folder2')->getSize());

		$this->scanner->backgroundScan();

		$this->assertEquals(6, $this->cache->get('folder2')->getSize());
	}
}
