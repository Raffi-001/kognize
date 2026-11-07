<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AnalysisStatus;
use App\Enums\AnalysisType;
use App\Enums\Ethics;
use App\Enums\Plan;
use App\Enums\SubmissionState;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::factory()->withoutTwoFactor()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'plan' => Plan::Pro,
            'is_admin' => true,
        ]);

        $submission = Submission::factory()->for($user)->create([
            'title' => 'Մեր քաղաքական գործիչները մշտապես խոստանում են, որ ամեն ինչ կփոխվի',
            'state' => SubmissionState::Analyzed,
        ]);

        $sentences = [
            'a1' => 'Մեր քաղաքական գործիչները մշտապես խոստանում են, որ ամեն ինչ կփոխվի, բայց իրականում ոչինչ չի փոխվում։',
            'a2' => 'Եթե դու համաձայն չես կառավարության հետ, ուրեմն դու հայրենիքի թշնամի ես։',
            'a3' => 'Բոլորը գիտեն, որ ընդդիմությունը վճարվում է արտասահմանից, ուստի նրանց ասածներին պետք չէ լրջորեն վերաբերվել։',
            'a4' => 'Մենք կամ պետք է ընդունենք այս բարեփոխումները հիմա, կամ էլ մեր երկիրը կկործանվի։',
            'a5' => 'Մեր նախնիները երբեք թույլ չէին տա, որ նման բան տեղի ունենա, ուստի մենք էլ չպետք է թույլ տանք։',
        ];

        $findings = [
            AnalysisType::Fallacy->value => [
                ['a2', 'False dilemma', 'Տեքստը ներկայացնում է միայն երկու տարբերակ՝ համաձայնություն կամ թշնամանք։', Ethics::Unethical, 8],
                ['a3', 'Ad hominem', 'Փաստարկը մերժվում է՝ հարձակվելով խոսողի վրա, ոչ թե նրա պնդման։', Ethics::Unethical, 7],
                ['a5', 'Appeal to tradition', 'Անցյալի պրակտիկան ներկայացվում է որպես ապացույց։', Ethics::Questionable, 5],
            ],
            AnalysisType::Rhetoric->value => [
                ['a4', 'Appeal to fear', 'Չափազանցված հետևանք՝ վախ առաջացնելու համար։', Ethics::Questionable, 7],
                ['a1', 'Hyperbole', 'Բոլոր քաղաքական գործիչներին վերագրվում է նույն վարքը։', Ethics::Neutral, 4],
            ],
            AnalysisType::Questions->value => [
                ['a3', 'Ի՞նչ ապացույց կա, որ ընդդիմությունը ֆինանսավորվում է արտասահմանից։', 'Պնդումը ներկայացվում է որպես հայտնի փաստ՝ առանց աղբյուրի։', Ethics::Neutral, 9],
                ['a1', 'Ո՞ր խոստումներն են կոնկրետ չկատարվել։', 'Ընդհանուր պնդումը պետք է հիմնավորվի օրինակներով։', Ethics::Neutral, 6],
            ],
        ];

        foreach ($findings as $type => $notes) {
            $type = AnalysisType::from($type);
            $catalog = $type->catalog();

            $analysis = $submission->analyses()->create([
                'type' => $type,
                'status' => AnalysisStatus::Completed,
            ]);

            foreach ($notes as $index => [$id, $heading, $text, $ethics, $strength]) {
                $analysis->notes()->create([
                    'annotation_id' => sprintf('%s-%d', $type->value, $index + 1),
                    'label' => $sentences[$id],
                    'aspect_label' => isset($catalog[$heading])
                        ? sprintf('%s (%s)', $catalog[$heading]['armenian'], $heading)
                        : $heading,
                    'text' => $text,
                    'ethics' => $ethics,
                    'strength' => $strength,
                ]);
            }
        }

        $submission->userNotes()->create([
            'user_id' => $user->id,
            'annotation_id' => 'un-demo',
            'label' => 'ընդդիմությունը վճարվում է արտասահմանից',
            'text' => 'This claim has been repeated widely but no source is given.',
        ]);
    }
}
