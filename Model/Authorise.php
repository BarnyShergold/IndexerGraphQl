<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Shergold\IndexerGraphQl\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Action\Context;

/**
 * Authorise methods for module.
 */
class Authorise
{
    private const INDEXER_SECRET_KEY = 'graphql_security/indexer/indexer_state_security_key';
    private const AUTH_HEADER = 'Indexer-Auth-Key';

    /**
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Context $context
     */
    public function __construct(
        private ScopeConfigInterface $scopeConfig,
        private Context $context
    ) {
        $this->request = $this->context->getRequest();
    }

    /**
     * Determines if the call is authorised
     *
     * @return bool
     */
    public function isCallAuthorised(): bool
    {
        return $this->getAuthHeader() === $this->getSecretKey();
    }

    /**
     * Get secret key from configuration
     *
     * @return string|null
     */
    private function getSecretKey(): string|null
    {
        return $this->scopeConfig->getValue(self::INDEXER_SECRET_KEY);
    }

    /**
     * Get value passed in header to authorise
     *
     * @return string|null
     */
    private function getAuthHeader(): string|null
    {
        return $this->request->getHeader(self::AUTH_HEADER);
    }
}
