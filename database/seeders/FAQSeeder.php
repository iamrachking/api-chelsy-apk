<?php

namespace Database\Seeders;

use App\Models\FAQ;
use Illuminate\Database\Seeder;

class FAQSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            [
                'question' => 'Quels sont vos horaires d\'ouverture ?',
                'answer' => 'Nous sommes ouverts du lundi au dimanche. Du lundi au jeudi de 9h à 22h, le vendredi et samedi de 9h à 23h, et le dimanche de 11h à 21h.',
                'order' => 1,
            ],
            [
                'question' => 'Livrez-vous à domicile ?',
                'answer' => 'Oui, nous livrons dans un rayon de 10 km autour du restaurant. Les frais de livraison sont calculés en fonction de la distance.',
                'order' => 2,
            ],
            [
                'question' => 'Quels sont les modes de paiement acceptés ?',
                'answer' => 'Nous acceptons les paiements par carte bancaire, Mobile Money (MTN, Moov) et paiement en espèces à la livraison.',
                'order' => 3,
            ],
            [
                'question' => 'Y a-t-il un montant minimum de commande ?',
                'answer' => 'Oui, le montant minimum de commande est de 5000 FCFA.',
                'order' => 4,
            ],
            [
                'question' => 'Combien de temps prend la préparation d\'une commande ?',
                'answer' => 'Le temps de préparation varie selon les plats, généralement entre 20 et 35 minutes. Vous pouvez voir le temps estimé sur chaque plat.',
                'order' => 5,
            ],
            [
                'question' => 'Puis-je annuler ma commande ?',
                'answer' => 'Vous pouvez annuler votre commande tant qu\'elle n\'a pas été confirmée par le restaurant. Après confirmation, veuillez nous contacter directement.',
                'order' => 6,
            ],
        ];

        foreach ($faqs as $faq) {
            FAQ::firstOrCreate(
                ['question' => $faq['question']],
                [
                    'answer' => $faq['answer'],
                    'order' => $faq['order'],
                    'is_active' => true,
                ]
            );
        }
    }
}
