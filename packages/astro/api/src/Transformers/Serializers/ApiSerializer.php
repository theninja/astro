<?php

namespace Astro\API\Transformers\Serializers;

use League\Fractal\Serializer\ArraySerializer;

class ApiSerializer extends ArraySerializer
{
	/**
	 * Serialize a collection.
	 *
	 * @param string $resourceKey
	 * @param array  $data
	 *
	 * @return array
	 */
	public function collection($resourceKey, array $data)
	{
		return [
			'data' => $data
		];
	}

	/**
	 * Serialize an item.
	 *
	 * @param string $resourceKey
	 * @param array  $data
	 *
	 * @return array
	 */
	public function item($resourceKey, array $data)
	{
		return [
			'data' => $data
		];
	}

	/**
	 * Serialize null resource.
	 *
	 * @return array
	 */
	public function null()
	{
		return [
			'data' => []
		];
	}
}
