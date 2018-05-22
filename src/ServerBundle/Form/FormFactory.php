<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\ServerBundle\Form;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

class FormFactory
{
    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $type;

    /**
     * FormFactory constructor.
     *
     * @param FormFactoryInterface $formFactory
     * @param string               $name
     * @param string               $type
     */
    public function __construct( FormFactoryInterface $formFactory, string $name, string $type)
    {
        $this->formFactory = $formFactory;
        $this->name = $name;
        $this->type = $type;
    }

    /**
     * @param array                    $options
     * @param Model\AuthorizationModel $data
     *
     * @return FormInterface
     */
    public function createForm(array $options = [], $data = null): FormInterface
    {
        $form = $this->formFactory->createNamed($this->name, $this->type, null, $options);
        if (null !== $data) {
            $form->setData($data);
        }

        return $form;
    }
}
