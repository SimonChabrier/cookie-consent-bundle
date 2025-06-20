<?php

declare(strict_types=1);

/*
 * This file is part of the ConnectHolland CookieConsentBundle package.
 * (c) Connect Holland.
 */

namespace ConnectHolland\CookieConsentBundle\Form;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use ConnectHolland\CookieConsentBundle\Cookie\CookieChecker;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class CookieConsentType extends AbstractType
{
    /**
     * @param CookieChecker $cookieChecker
     * @param array<string> $cookieCategories
     * @param bool          $cookieConsentSimplified
     * @param bool          $csrfProtection
     */
    public function __construct(
        protected CookieChecker $cookieChecker,
        protected array $cookieCategories,
        protected bool $cookieConsentSimplified = false,
        protected bool $csrfProtection = true
    ) {}


    /**
     * Build the cookie consent form.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        foreach ($this->cookieCategories as $category) {
            $builder->add($category, CheckboxType::class, [
                'required'  => false,
                'data'      => $this->cookieChecker->isCookieConsentSavedByUser()
                    ? $this->cookieChecker->isCategoryAllowedByUser($category)
                    : true,
                'label_attr' => ['class' => 'checkbox-switch'],
                'attr'       => ['class' => 'form-check-input'],
            ]);
        }

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event): void {
            $data = $event->getData();

            foreach ($this->cookieCategories as $category) {
                $data[$category] = ($data[$category] ?? false) ? 'true' : 'false';
            }

            $event->setData($data);
        });

        if ($this->cookieConsentSimplified === false) {
            $builder->add('save', SubmitType::class, ['label' => 'ch_cookie_consent.save', 'attr' => ['class' => 'btn ch-cookie-consent__btn']]);
        } else {
            $builder->add('use_only_functional_cookies', SubmitType::class, ['label' => 'ch_cookie_consent.use_only_functional_cookies', 'attr' => ['class' => 'btn ch-cookie-consent__btn']]);
            $builder->add('use_all_cookies', SubmitType::class, ['label' => 'ch_cookie_consent.use_all_cookies', 'attr' => ['class' => 'btn ch-cookie-consent__btn ch-cookie-consent__btn--secondary']]);

            $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
                $data = $event->getData();

                foreach ($this->cookieCategories as $category) {
                    $data[$category] = isset($data['use_all_cookies']) ? 'true' : 'false';
                }

                $event->setData($data);
            });
        }
    }





    /**
     * Default options.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'translation_domain' => 'CHCookieConsentBundle',
            'csrf_protection'    => $this->csrfProtection,
        ]);
    }
}
