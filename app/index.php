<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Aws\S3\S3Client;
use Bref\Context\Context;
use Bref\Event\InvalidLambdaEvent;
use Bref\Event\S3\S3Event;
use Bref\Event\S3\S3Handler;
use Bref\Logger\StderrLogger;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use ZBateson\MailMimeParser\MailMimeParser;

/**
 * 添付ファイルの情報を保持するDTO
 */
final readonly class AttachedFileDto
{
    /**
     * @param string $fileName
     * @param string $contentType
     * @param StreamInterface $content
     */
    public function __construct(
        public string $fileName,
        public string $contentType,
        public StreamInterface $content
    ) {}
}

/**
 * S3のEventハンドラー
 */
final class Handler extends S3Handler
{
    /**
     * @var string
     */
    private const RECEIPT_EMAIL_BUCKET_NAME = 'bref-php-ses-sample';

    /**
     * @var string
     */
    private const PUT_FILE_BUCKET_NAME = 'bref-php-sample';

    /**
     * @var LoggerInterface
     */
    private readonly LoggerInterface $logger;

    /**
     * @var MailMimeParser
     */
    private readonly MailMimeParser $mailParser;

    /**
     * @var S3Client
     */
    private readonly S3Client $s3Client;

    /**
     * @param LoggerInterface $logger
     * @param MailMimeParser $mailParser
     * @param S3Client $s3Client
     */
    public function __construct(LoggerInterface $logger, MailMimeParser $mailParser, S3Client $s3Client)
    {
        $this->logger     = $logger;
        $this->mailParser = $mailParser;
        $this->s3Client   = $s3Client;
    }

    /**
     * @param S3Event $event
     * @param Context $context
     *
     * @throws InvalidLambdaEvent
     */
    public function handleS3(S3Event $event, Context $context): void
    {
        $this->logger->info('=== START ===');

        $mail = $this->getMailObject($event);

        $this->logger->info('succeed: get mail object');

        $file = $this->retrieveMailAttachedFile($mail);

        if ($file === null) {
            return;
        }

        $this->logger->info('succeed: retrieve mail attached file');

        $this->putFile($file);

        $this->logger->info('succeed: put file');

        $this->logger->info('=== END ===');
    }

    /**
     * @param S3Event $event
     *
     * @return StreamInterface
     *
     * @throws InvalidLambdaEvent
     */
    private function getMailObject(S3Event $event): StreamInterface
    {
        $record = $event->getRecords()[0];

        return $this->s3Client->getObject([
            'Bucket' => self::RECEIPT_EMAIL_BUCKET_NAME,
            'Key'    => $record->getObject()->getKey(),
        ])->get('Body');
    }

    /**
     * @param StreamInterface $file
     *
     * @return AttachedFileDto|null
     */
    private function retrieveMailAttachedFile(StreamInterface $file): ?AttachedFileDto
    {
        $message = $this->mailParser->parse($file, false);

        for ($i = 0; $i < $message->getAttachmentCount(); $i++) {
            $part = $message->getAttachmentPart($i);

            if ($part === null) {
                continue;
            }

            return new AttachedFileDto(
                $part->getFileName(),
                $part->getContentType(),
                $part->getContentStream()
            );
        }

        return null;
    }

    /**
     * @param AttachedFileDto $file
     *
     * @return void
     */
    private function putFile(AttachedFileDto $file): void
    {
        $this->s3Client->putObject([
            'Bucket'      => self::PUT_FILE_BUCKET_NAME,
            'Key'         => $file->fileName,
            'contentType' => $file->contentType,
            'Body'        => (string) $file->content,
        ]);
    }
}

return new Handler(
    new StderrLogger(LogLevel::INFO),
    new MailMimeParser(),
    new S3Client(['region' => 'ap-northeast-1'])
);
