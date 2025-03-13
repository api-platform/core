<?php

namespace ApiPlatform\Laravel\Eloquent\State;

use ApiPlatform\Metadata\Operation;
use Illuminate\Database\Eloquent\Model;

trait ModelInstantiationTrait
{
    /**
     * Instantiates the model from the operation.
     *
     * @param Operation $operation
     * @return Model
     */
    protected function instantiateModel(Operation $operation): Model
    {
        $modelClass = $operation->getClass();

        if (($options = $operation->getStateOptions()) && $options instanceof Options && $options->getModelClass()) {
            $modelClass = $options->getModelClass();
        }

        return new $modelClass();
    }
}
