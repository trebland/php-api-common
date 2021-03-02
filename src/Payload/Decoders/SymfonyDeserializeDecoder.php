<?php
	namespace DaybreakStudios\RestApiCommon\Payload\Decoders;

	use DaybreakStudios\RestApiCommon\Error\Errors\Validation\ValidationFailedError;
	use DaybreakStudios\RestApiCommon\Exceptions\ApiErrorException;
	use DaybreakStudios\RestApiCommon\Payload\DecoderIntent;
	use DaybreakStudios\RestApiCommon\Payload\Exceptions\PayloadDecoderException;
	use DaybreakStudios\RestApiCommon\Payload\PayloadDecoderInterface;
	use Symfony\Component\Serializer\SerializerInterface;
	use Symfony\Component\Validator\Constraint;
	use Symfony\Component\Validator\Validator\ValidatorInterface;

	class SymfonyDeserializeDecoder implements PayloadDecoderInterface {
		public const GROUP_CREATE = 'create';
		public const GROUP_UPDATE = 'update';

		/**
		 * @var SerializerInterface
		 */
		protected SerializerInterface $serializer;

		/**
		 * @var string
		 */
		protected string $defaultFormat;

		/**
		 * @var string
		 */
		protected string $payloadClass;

		/**
		 * @var ValidatorInterface|null
		 */
		protected ?ValidatorInterface $validator;

		/**
		 * @var string[]
		 */
		protected array $createGroups = [];

		/**
		 * @var string[]
		 */
		protected array $updateGroups = [];

		/**
		 * @var array
		 */
		protected array $deserializeContext = [];

		/**
		 * SymfonyDeserializeDecoder constructor.
		 *
		 * @param SerializerInterface     $serializer
		 * @param string                  $defaultFormat
		 * @param string                  $payloadClass
		 * @param ValidatorInterface|null $validator
		 */
		public function __construct(
			SerializerInterface $serializer,
			string $defaultFormat,
			string $payloadClass,
			ValidatorInterface $validator = null
		) {
			$this->serializer = $serializer;
			$this->defaultFormat = $defaultFormat;
			$this->payloadClass = $payloadClass;
			$this->validator = $validator;

			if ($validator) {
				$this->createGroups = [
					Constraint::DEFAULT_GROUP,
					static::GROUP_CREATE,
				];

				$this->updateGroups = [
					Constraint::DEFAULT_GROUP,
					static::GROUP_UPDATE,
				];
			}
		}

		/**
		 * {@inheritdoc}
		 */
		public function parse(string $intent, string $input, ?string $format = null): object {
			$payload = $this->serializer->deserialize(
				$input,
				$this->getPayloadClass(),
				$format ?? $this->getDefaultFormat(),
				$this->getDeserializeContext()
			);

			if ($this->validator) {
				if ($intent === DecoderIntent::CREATE)
					$groups = $this->getCreateGroups();
				else if ($intent === DecoderIntent::UPDATE)
					$groups = $this->getUpdateGroups();
				else
					throw PayloadDecoderException::invalidIntent($intent);

				$failures = $this->validator->validate($payload, null, $groups);

				if ($failures->count())
					throw new ApiErrorException(new ValidationFailedError($failures));
			}

			return $payload;
		}

		/**
		 * @return string
		 */
		public function getDefaultFormat(): string {
			return $this->defaultFormat;
		}

		/**
		 * @param string $defaultFormat
		 *
		 * @return $this
		 */
		public function setDefaultFormat(string $defaultFormat): SymfonyDeserializeDecoder {
			$this->defaultFormat = $defaultFormat;

			return $this;
		}

		/**
		 * @return string
		 */
		public function getPayloadClass(): string {
			return $this->payloadClass;
		}

		/**
		 * @param string $payloadClass
		 *
		 * @return $this
		 */
		public function setPayloadClass(string $payloadClass): SymfonyDeserializeDecoder {
			$this->payloadClass = $payloadClass;

			return $this;
		}

		/**
		 * @return string[]
		 */
		public function getCreateGroups(): array {
			return $this->createGroups;
		}

		/**
		 * @param string[] $createGroups
		 *
		 * @return $this
		 */
		public function setCreateGroups(array $createGroups): SymfonyDeserializeDecoder {
			$this->createGroups = $createGroups;

			return $this;
		}

		/**
		 * @return string[]
		 */
		public function getUpdateGroups(): array {
			return $this->updateGroups;
		}

		/**
		 * @param string[] $updateGroups
		 *
		 * @return $this
		 */
		public function setUpdateGroups(array $updateGroups): SymfonyDeserializeDecoder {
			$this->updateGroups = $updateGroups;

			return $this;
		}

		/**
		 * @return array
		 */
		public function getDeserializeContext(): array {
			return $this->deserializeContext;
		}

		/**
		 * @param array $deserializeContext
		 *
		 * @return $this
		 */
		public function setDeserializeContext(array $deserializeContext): SymfonyDeserializeDecoder {
			$this->deserializeContext = $deserializeContext;

			return $this;
		}
	}