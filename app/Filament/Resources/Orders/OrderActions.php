<?php

namespace App\Filament\Resources\Orders;

use App\Enums\OrderStatus;
use App\Mail\OrderShipped;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/** Actions shared by the order list and the order page. */
class OrderActions
{
    public const CARRIERS = [
        'Colissimo' => 'Colissimo',
        'Chronopost' => 'Chronopost',
        'Lettre recommandée' => 'Lettre recommandée',
        'DHL' => 'DHL',
        'UPS' => 'UPS',
        'Remise en main propre' => 'Remise en main propre',
        'Autre' => 'Autre',
    ];

    public static function markShipped(): Action
    {
        return Action::make('mark_shipped')
            ->label('Marquer comme expédiée')
            ->icon(Heroicon::OutlinedTruck)
            ->color('primary')
            ->visible(fn (Order $record) => $record->status === OrderStatus::Paid)
            ->modalDescription('Le client reçoit un e-mail avec le numéro de suivi.')
            ->schema([
                Select::make('carrier')->label('Transporteur')->options(self::CARRIERS)->default('Colissimo')->required(),
                TextInput::make('tracking_number')->label('Numéro de suivi')->maxLength(100),
                TextInput::make('tracking_url')
                    ->label('Lien de suivi (facultatif)')
                    ->helperText('Pour Colissimo, le lien La Poste est ajouté automatiquement.')
                    ->url()
                    ->maxLength(255),
                Toggle::make('notify')->label('Envoyer l’e-mail au client')->default(true),
            ])
            ->action(function (Order $record, array $data) {
                $trackingUrl = $data['tracking_url'] ?: (
                    $data['carrier'] === 'Colissimo' && filled($data['tracking_number'])
                        ? 'https://www.laposte.fr/outils/suivre-vos-envois?code='.urlencode($data['tracking_number'])
                        : null
                );

                $record->update([
                    'status' => OrderStatus::Shipped,
                    'carrier' => $data['carrier'],
                    'tracking_number' => $data['tracking_number'],
                    'tracking_url' => $trackingUrl,
                    'shipped_at' => now(),
                ]);

                if ($data['notify']) {
                    try {
                        Mail::to($record->customer_email)->locale($record->locale)->send(new OrderShipped($record));
                    } catch (Throwable $e) {
                        Log::error('Shipping email failed', ['order' => $record->number, 'error' => $e->getMessage()]);
                        Notification::make()->title('Commande marquée expédiée, mais l’e-mail n’a pas pu partir.')->body($e->getMessage())->danger()->send();

                        return;
                    }
                }

                Notification::make()->title('Commande marquée comme expédiée')->success()->send();
            });
    }

    public static function markDelivered(): Action
    {
        return Action::make('mark_delivered')
            ->label('Marquer comme livrée')
            ->icon(Heroicon::OutlinedCheckBadge)
            ->color('success')
            ->requiresConfirmation()
            ->visible(fn (Order $record) => $record->status === OrderStatus::Shipped)
            ->action(fn (Order $record) => $record->update(['status' => OrderStatus::Delivered, 'delivered_at' => now()]));
    }

    public static function invoice(): Action
    {
        return Action::make('invoice')
            ->label('Facture')
            ->icon(Heroicon::OutlinedDocumentText)
            ->color('gray')
            ->url(fn (Order $record) => route('admin.orders.document', [$record, 'invoice']), shouldOpenInNewTab: true);
    }

    public static function packingSlip(): Action
    {
        return Action::make('packing_slip')
            ->label('Bon de livraison')
            ->icon(Heroicon::OutlinedClipboardDocumentList)
            ->color('gray')
            ->url(fn (Order $record) => route('admin.orders.document', [$record, 'packing-slip']), shouldOpenInNewTab: true);
    }

    public static function openInStripe(): Action
    {
        return Action::make('stripe')
            ->label('Voir dans Stripe')
            ->tooltip('Remboursements et litiges se gèrent dans Stripe.')
            ->icon(Heroicon::OutlinedCreditCard)
            ->color('gray')
            ->visible(fn (Order $record) => $record->stripePaymentUrl() !== null)
            ->url(fn (Order $record) => $record->stripePaymentUrl(), shouldOpenInNewTab: true);
    }

    public static function editNotes(): Action
    {
        return Action::make('notes')
            ->label('Notes')
            ->icon(Heroicon::OutlinedPencilSquare)
            ->color('gray')
            ->fillForm(fn (Order $record) => [
                'admin_notes' => $record->admin_notes,
                'needs_attention' => $record->needs_attention,
            ])
            ->schema([
                Textarea::make('admin_notes')->label('Notes internes')->rows(5),
                Toggle::make('needs_attention')->label('À vérifier'),
            ])
            ->action(fn (Order $record, array $data) => $record->update($data));
    }
}
