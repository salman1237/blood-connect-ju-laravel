<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Generated on demand rather than a committed binary file, so the columns
 * can never drift out of sync with what App\Imports\DonorsImport actually
 * expects.
 */
class DonorImportTemplateExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        return [
            [
                'Rahim Uddin',
                'rahim.uddin@example.com',
                'student',
                'male',
                '2000-01-15',
                'O+',
                'Al Beruni Hall',
                'Computer Science and Engineering',
                '2020-21',
                '01712345678',
                1,
                '',
                'public',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'name', 'email', 'role', 'gender', 'date_of_birth', 'blood_group',
            'hall', 'department', 'batch', 'phone', 'phone_has_whatsapp',
            'whatsapp_number', 'phone_visibility',
        ];
    }
}
