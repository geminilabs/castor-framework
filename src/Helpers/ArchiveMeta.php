<?php

namespace GeminiLabs\Castor\Helpers;

use GeminiLabs\Castor\Helpers\SiteMeta;

/**
 * ArchiveMeta::all();
 * ArchiveMeta::group();
 * ArchiveMeta::group('option','fallback');
 * ArchiveMeta::get('group');
 * ArchiveMeta::get('group','option','fallback');
 *
 * @property object all
 */
class ArchiveMeta extends SiteMeta
{
	protected $options;

	public function __construct()
	{
		$this->options = get_option( apply_filters( 'pollux/archives/id', 'pollux_archives' ), [] );
	}

	/**
	 * @param string|null $key
	 * @param mixed $fallback
	 * @param string $group
	 * @return mixed
	 */
	public function get( $key = '', $fallback = null, $group = '' )
	{
		return parent::get( $group, $key, $fallback );
	}

	/**
	 * @return string
	 */
	protected function getDefaultGroup()
	{
		return get_post_type();
	}
}