<?php

namespace League\Flysystem\Plugin;

use League\Flysystem\Dropbox\DropboxAdapter;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\PluginInterface;
use League\Flysystem\Util;

class DropboxDelta implements PluginInterface
{
	/**
	 * @var Filesystem
	 */
	protected $filesystem;

	/**
	 * @var DropboxAdapter
	 */
	protected $adapter;

	/**
	 * @see DropboxAdapter (copied)
	 * @var array
	 */
	protected static $resultMap = [
		'bytes'     => 'size',
		'mime_type' => 'mimetype',
	];

	public function setFilesystem(FilesystemInterface $filesystem)
	{
		$this->filesystem = $filesystem;
	}

	public function getMethod()
	{
		return 'getDelta';
	}

	public function handle($cursor = null)
	{
		$this->adapter = $this->filesystem->getAdapter();
		$client = $this->adapter->getClient();

		$prefix = $this->adapter->getPathPrefix() ? '/' . rtrim($this->adapter->getPathPrefix(), '/') : null;

		$arrDelta = $client->getDelta($cursor, $prefix);

		/**
		 * @var int   $i
		 * @var array $entry [0] => <path>
		 *                   [1] => <metadata>|null if entry was deleted
		 */
		foreach ($arrDelta['entries'] as $i => $entry)
		{
			$arrDelta['entries'][$i][0] = ltrim($this->adapter->removePathPrefix($arrDelta['entries'][$i][0]), '/');
			$arrDelta['entries'][$i][1] = $this->normalizeResponse($arrDelta['entries'][$i][1]);
		}

		return $arrDelta;
	}

	/**
	 * Normalize a Dropbox response.
	 * @see DropboxAdapter (copied)
	 *
	 * @param array $response
	 *
	 * @return array|null if the entry was deleted in user's dropbox
	 */
	protected function normalizeResponse($response)
	{
		// Check for null
		if (null === $response)
		{
			return null;
		}

		$result = ['path' => ltrim($this->adapter->removePathPrefix($response['path']), '/')];

		if (isset($response['modified']))
		{
			$result['timestamp'] = strtotime($response['modified']);
		}

		$result = array_merge($result, Util::map($response, static::$resultMap));
		$result['type'] = $response['is_dir'] ? 'dir' : 'file';

		return $result;
	}
}
