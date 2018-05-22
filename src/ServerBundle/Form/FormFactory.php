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
use Symfony\Component\Translation\TranslatorInterface;

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
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * FormFactory constructor.
     *
     * @param TranslatorInterface  $translator
     * @param FormFactoryInterface $formFactory
     * @param string               $name
     * @param string               $type
     */
    public function __construct(TranslatorInterface $translator, FormFactoryInterface $formFactory, string $name, string $type)
    {
        $this->translator = $translator;
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
        $options = ['translator' => $this->translator] + $options;

        $form = $this->formFactory->createNamed($this->name, $this->type, null, $options);
        if (null !== $data) {
            $form->setData($data);
        }

        return $form;
    }
}
