<?php

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Testing\TaskProcessing;

use OCA\Testing\AppInfo\Application;
use OCP\AppFramework\Services\IAppConfig;
use OCP\TaskProcessing\EShapeType;
use OCP\TaskProcessing\Exception\ProcessingException;
use OCP\TaskProcessing\ISynchronousProvider;
use OCP\TaskProcessing\ShapeDescriptor;
use OCP\TaskProcessing\ShapeEnumValue;
use OCP\TaskProcessing\TaskTypes\ContextWrite;
use RuntimeException;

class FakeContextWriteProvider implements ISynchronousProvider {

	public function __construct(
		protected IAppConfig $appConfig,
	) {
	}

	public function getId(): string {
		return Application::APP_ID . '-contextwrite';
	}

	public function getName(): string {
		return 'Fake context write task processing provider';
	}

	public function getTaskTypeId(): string {
		return ContextWrite::ID;
	}

	public function getExpectedRuntime(): int {
		return 1;
	}

	public function getInputShapeEnumValues(): array {
		return [];
	}

	public function getInputShapeDefaults(): array {
		return [];
	}

	public function getOptionalInputShape(): array {
		return [
			'max_tokens' => new ShapeDescriptor(
				'Maximum output words',
				'The maximum number of words/tokens that can be generated in the completion.',
				EShapeType::Number
			),
			'model' => new ShapeDescriptor(
				'Model',
				'The model used to generate the completion',
				EShapeType::Enum
			),
		];
	}

	public function getOptionalInputShapeEnumValues(): array {
		return [
			'model' => [
				new ShapeEnumValue('Model 1', 'model_1'),
				new ShapeEnumValue('Model 2', 'model_2'),
				new ShapeEnumValue('Model 3', 'model_3'),
			],
		];
	}

	public function getOptionalInputShapeDefaults(): array {
		return [
			'max_tokens' => 4321,
			'model' => 'model_2',
		];
	}

	public function getOutputShapeEnumValues(): array {
		return [];
	}

	public function getOptionalOutputShape(): array {
		return [];
	}

	public function getOptionalOutputShapeEnumValues(): array {
		return [];
	}

	public function process(?string $userId, array $input, callable $reportProgress): array {
		if ($this->appConfig->getAppValueBool('fail-' . $this->getId())) {
			throw new ProcessingException('Failing as set by AppConfig');
		}

		if (
			!isset($input['style_input']) || !is_string($input['style_input'])
				|| !isset($input['source_input']) || !is_string($input['source_input'])
		) {
			throw new RuntimeException('Invalid inputs');
		}
		$writingStyle = $input['style_input'];
		$sourceMaterial = $input['source_input'];

		if (isset($input['model']) && is_string($input['model'])) {
			$model = $input['model'];
		} else {
			$model = 'unknown model';
		}

		$maxTokens = null;
		if (isset($input['max_tokens']) && is_int($input['max_tokens'])) {
			$maxTokens = $input['max_tokens'];
		}

		$fakeResult = 'This is a fake result: '
			. "\n\n- Style input: " . $writingStyle
			. "\n- Source input: " . $sourceMaterial
			. "\n- Model: " . $model
			. "\n- Maximum number of words: " . $maxTokens;

		return ['output' => $fakeResult];
	}
}
