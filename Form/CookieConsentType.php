<?php

declare(strict_types=1);

/*
 * This file is part of the ConnectHolland CookieConsentBundle package.
 * (c) Connect Holland.
 */

namespace ConnectHolland\CookieConsentBundle\Form;

use ConnectHolland\CookieConsentBundle\Cookie\CookieChecker;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
        // foreach ($this->cookieCategories as $category) {
        //     $builder->add($category, ChoiceType::class, [
        //         'expanded' => true,
        //         'multiple' => false,
        //         'data'     => $this->cookieChecker->isCategoryAllowedByUser($category) ? 'true' : 'false',
        //         'choices'  => [
        //             ['ch_cookie_consent.yes' => 'true'],
        //             ['ch_cookie_consent.no' => 'false'],
        //         ],
        //     ]);
        // }

        foreach ($this->cookieCategories as $category) {
            $builder->add($category, CheckboxType::class, [
                'label' => "ch_cookie_consent.$category",
                'data' => $this->cookieChecker->isCategoryAllowedByUser($category), // true/false par défaut
                'attr' => ['class' => 'form-check-input'],
                'label_attr' => ['class' => 'checkbox-switch'],
                'row_attr' => ['class' => 'form-check form-switch ch-cookie-consent__category-toggle'],
            ]);
        }

        // on écoute l'événement PRE_SUBMIT pour gérer les données soumises et définir si ch_cookie_consent.yes ou ch_cookie_consent.no en fonction de l'état des cases à cocher
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            foreach ($this->cookieCategories as $category) {
                // Si la case est cochée, on met 'true', sinon 'false'
                $data[$category] = isset($data[$category]) ? 'ch_cookie_consent.yes' : 'ch_cookie_consent.no';
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
