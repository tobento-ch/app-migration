<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

declare(strict_types=1);

namespace Tobento\App\Migration\Test\Mock;

use Tobento\Service\Migration\ActionInterface;
use Tobento\Service\Migration\ActionFailedException;

/**
 * Action
 */
class Action implements ActionInterface
{    
    /**
     * Create a new Action
     *
     * @param string $name
     * @param string $type
     */    
    public function __construct(
        protected string $name,
        protected string $type = '',
    ) {}
        
    /**
     * Process the action.
     *
     * @return void
     * @throws ActionFailedException
     */    
    public function process(): void
    {
        //
    }
    

    /**
     * Returns a name of the action.
     *
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Returns a description of the action.
     *
     * @return string
     */    
    public function description(): string
    {
        return $this->name;
    }
    
    /**
     * Returns the type of the action.
     *
     * @return string
     */
    public function type(): string
    {
        return $this->type;
    }
    
    /**
     * Returns the processed data information.
     *
     * @return array<array-key, string>
     */
    public function processedDataInfo(): array
    {
        return ['key' => 'name'];
    }
}