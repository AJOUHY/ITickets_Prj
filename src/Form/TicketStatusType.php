<?php

namespace App\Form;

use App\Entity\Ticket;
use App\Enum\TicketStatus;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TicketStatusType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('status', ChoiceType::class, [
            'label' => 'Statut',
            'choices' => [
                'Ouvert' => TicketStatus::OPENED,
                'En cours' => TicketStatus::IN_PROGRESS,
                'En attente' => TicketStatus::PENDING,
                'Résolu' => TicketStatus::RESOLVED,
                'Fermé' => TicketStatus::CLOSED,
            ],
            'choice_label' => fn ($choice) => match ($choice) {
                TicketStatus::OPENED => 'Ouvert',
                TicketStatus::IN_PROGRESS => 'En cours',
                TicketStatus::PENDING => 'En attente',
                TicketStatus::RESOLVED => 'Résolu',
                TicketStatus::CLOSED => 'Fermé',
                default => (string) $choice,
            },
            'choice_value' => static fn (?TicketStatus $choice) => $choice?->value,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ticket::class,
        ]);
    }
}
