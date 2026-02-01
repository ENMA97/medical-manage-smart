<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegionsAndCountiesSeeder extends Seeder
{
    public function run(): void
    {
        $regions = [
            [
                'code' => 'RY',
                'name' => 'Riyadh',
                'name_ar' => 'الرياض',
                'counties' => [
                    ['name' => 'Riyadh', 'name_ar' => 'الرياض', 'code' => 'RY-RY'],
                    ['name' => 'Diriyah', 'name_ar' => 'الدرعية', 'code' => 'RY-DR'],
                    ['name' => 'Al-Kharj', 'name_ar' => 'الخرج', 'code' => 'RY-KH'],
                    ['name' => 'Dawadmi', 'name_ar' => 'الدوادمي', 'code' => 'RY-DW'],
                    ['name' => 'Al-Majmaah', 'name_ar' => 'المجمعة', 'code' => 'RY-MJ'],
                    ['name' => 'Al-Quway\'iyah', 'name_ar' => 'القويعية', 'code' => 'RY-QW'],
                    ['name' => 'Wadi Ad-Dawasir', 'name_ar' => 'وادي الدواسر', 'code' => 'RY-WD'],
                    ['name' => 'Al-Aflaj', 'name_ar' => 'الأفلاج', 'code' => 'RY-AF'],
                    ['name' => 'Zulfi', 'name_ar' => 'الزلفي', 'code' => 'RY-ZL'],
                    ['name' => 'Shaqra', 'name_ar' => 'شقراء', 'code' => 'RY-SH'],
                    ['name' => 'Hotat Bani Tamim', 'name_ar' => 'حوطة بني تميم', 'code' => 'RY-HT'],
                    ['name' => 'Afif', 'name_ar' => 'عفيف', 'code' => 'RY-AFF'],
                    ['name' => 'As-Sulayyil', 'name_ar' => 'السليل', 'code' => 'RY-SL'],
                    ['name' => 'Dharma', 'name_ar' => 'ضرما', 'code' => 'RY-DH'],
                    ['name' => 'Al-Muzahmiyya', 'name_ar' => 'المزاحمية', 'code' => 'RY-MZ'],
                    ['name' => 'Rumah', 'name_ar' => 'رماح', 'code' => 'RY-RM'],
                    ['name' => 'Thadiq', 'name_ar' => 'ثادق', 'code' => 'RY-TH'],
                    ['name' => 'Huraimla', 'name_ar' => 'حريملاء', 'code' => 'RY-HR'],
                    ['name' => 'Al-Hariq', 'name_ar' => 'الحريق', 'code' => 'RY-HQ'],
                    ['name' => 'Al-Ghat', 'name_ar' => 'الغاط', 'code' => 'RY-GH'],
                    ['name' => 'Marat', 'name_ar' => 'مرات', 'code' => 'RY-MR'],
                ],
            ],
            [
                'code' => 'MK',
                'name' => 'Makkah',
                'name_ar' => 'مكة المكرمة',
                'counties' => [
                    ['name' => 'Makkah', 'name_ar' => 'مكة المكرمة', 'code' => 'MK-MK'],
                    ['name' => 'Jeddah', 'name_ar' => 'جدة', 'code' => 'MK-JD'],
                    ['name' => 'Taif', 'name_ar' => 'الطائف', 'code' => 'MK-TF'],
                    ['name' => 'Al-Qunfudhah', 'name_ar' => 'القنفذة', 'code' => 'MK-QN'],
                    ['name' => 'Al-Lith', 'name_ar' => 'الليث', 'code' => 'MK-LT'],
                    ['name' => 'Rabigh', 'name_ar' => 'رابغ', 'code' => 'MK-RB'],
                    ['name' => 'Al-Jumum', 'name_ar' => 'الجموم', 'code' => 'MK-JM'],
                    ['name' => 'Khulais', 'name_ar' => 'خليص', 'code' => 'MK-KL'],
                    ['name' => 'Al-Kamil', 'name_ar' => 'الكامل', 'code' => 'MK-KM'],
                    ['name' => 'Turubah', 'name_ar' => 'تربة', 'code' => 'MK-TR'],
                    ['name' => 'Misan', 'name_ar' => 'ميسان', 'code' => 'MK-MS'],
                    ['name' => 'Adham', 'name_ar' => 'أضم', 'code' => 'MK-AD'],
                ],
            ],
            [
                'code' => 'MD',
                'name' => 'Madinah',
                'name_ar' => 'المدينة المنورة',
                'counties' => [
                    ['name' => 'Madinah', 'name_ar' => 'المدينة المنورة', 'code' => 'MD-MD'],
                    ['name' => 'Yanbu', 'name_ar' => 'ينبع', 'code' => 'MD-YN'],
                    ['name' => 'Al-Ula', 'name_ar' => 'العلا', 'code' => 'MD-UL'],
                    ['name' => 'Mahd Al-Dhahab', 'name_ar' => 'مهد الذهب', 'code' => 'MD-MH'],
                    ['name' => 'Al-Hanakiyah', 'name_ar' => 'الحناكية', 'code' => 'MD-HN'],
                    ['name' => 'Badr', 'name_ar' => 'بدر', 'code' => 'MD-BD'],
                    ['name' => 'Khaybar', 'name_ar' => 'خيبر', 'code' => 'MD-KH'],
                ],
            ],
            [
                'code' => 'QS',
                'name' => 'Qassim',
                'name_ar' => 'القصيم',
                'counties' => [
                    ['name' => 'Buraydah', 'name_ar' => 'بريدة', 'code' => 'QS-BR'],
                    ['name' => 'Unaizah', 'name_ar' => 'عنيزة', 'code' => 'QS-UN'],
                    ['name' => 'Ar-Rass', 'name_ar' => 'الرس', 'code' => 'QS-RS'],
                    ['name' => 'Al-Mithnab', 'name_ar' => 'المذنب', 'code' => 'QS-MT'],
                    ['name' => 'Al-Bukayriyah', 'name_ar' => 'البكيرية', 'code' => 'QS-BK'],
                    ['name' => 'Al-Badayea', 'name_ar' => 'البدائع', 'code' => 'QS-BA'],
                    ['name' => 'Uyun Al-Jiwa', 'name_ar' => 'عيون الجواء', 'code' => 'QS-UJ'],
                    ['name' => 'Ash-Shimasiyah', 'name_ar' => 'الشماسية', 'code' => 'QS-SH'],
                    ['name' => 'Dharia', 'name_ar' => 'ضرية', 'code' => 'QS-DH'],
                    ['name' => 'Al-Asyah', 'name_ar' => 'الأسياح', 'code' => 'QS-AS'],
                    ['name' => 'An-Nabhaniyah', 'name_ar' => 'النبهانية', 'code' => 'QS-NB'],
                    ['name' => 'Riyadh Al-Khabra', 'name_ar' => 'رياض الخبراء', 'code' => 'QS-RK'],
                ],
            ],
            [
                'code' => 'EP',
                'name' => 'Eastern Province',
                'name_ar' => 'المنطقة الشرقية',
                'counties' => [
                    ['name' => 'Dammam', 'name_ar' => 'الدمام', 'code' => 'EP-DM'],
                    ['name' => 'Al-Ahsa', 'name_ar' => 'الأحساء', 'code' => 'EP-AH'],
                    ['name' => 'Hafar Al-Batin', 'name_ar' => 'حفر الباطن', 'code' => 'EP-HB'],
                    ['name' => 'Jubail', 'name_ar' => 'الجبيل', 'code' => 'EP-JB'],
                    ['name' => 'Al-Qatif', 'name_ar' => 'القطيف', 'code' => 'EP-QT'],
                    ['name' => 'Al-Khobar', 'name_ar' => 'الخبر', 'code' => 'EP-KB'],
                    ['name' => 'Dhahran', 'name_ar' => 'الظهران', 'code' => 'EP-DH'],
                    ['name' => 'Ras Tanura', 'name_ar' => 'رأس تنورة', 'code' => 'EP-RT'],
                    ['name' => 'Abqaiq', 'name_ar' => 'بقيق', 'code' => 'EP-AB'],
                    ['name' => 'An-Nairiyah', 'name_ar' => 'النعيرية', 'code' => 'EP-NR'],
                    ['name' => 'Qaryat Al-Ulya', 'name_ar' => 'قرية العليا', 'code' => 'EP-QU'],
                    ['name' => 'Khafji', 'name_ar' => 'الخفجي', 'code' => 'EP-KF'],
                ],
            ],
            [
                'code' => 'AS',
                'name' => 'Asir',
                'name_ar' => 'عسير',
                'counties' => [
                    ['name' => 'Abha', 'name_ar' => 'أبها', 'code' => 'AS-AB'],
                    ['name' => 'Khamis Mushait', 'name_ar' => 'خميس مشيط', 'code' => 'AS-KM'],
                    ['name' => 'Bisha', 'name_ar' => 'بيشة', 'code' => 'AS-BS'],
                    ['name' => 'An-Namas', 'name_ar' => 'النماص', 'code' => 'AS-NM'],
                    ['name' => 'Muhayil', 'name_ar' => 'محايل', 'code' => 'AS-MH'],
                    ['name' => 'Sarat Abidah', 'name_ar' => 'سراة عبيدة', 'code' => 'AS-SA'],
                    ['name' => 'Rijal Almaa', 'name_ar' => 'رجال ألمع', 'code' => 'AS-RA'],
                    ['name' => 'Tathlith', 'name_ar' => 'تثليث', 'code' => 'AS-TT'],
                    ['name' => 'Ahad Rufaidah', 'name_ar' => 'أحد رفيدة', 'code' => 'AS-AR'],
                    ['name' => 'Dhahran Al-Janoub', 'name_ar' => 'ظهران الجنوب', 'code' => 'AS-DJ'],
                    ['name' => 'Balqarn', 'name_ar' => 'بلقرن', 'code' => 'AS-BQ'],
                    ['name' => 'Tanomah', 'name_ar' => 'تنومة', 'code' => 'AS-TN'],
                    ['name' => 'Al-Majardah', 'name_ar' => 'المجاردة', 'code' => 'AS-MJ'],
                    ['name' => 'Al-Birk', 'name_ar' => 'البرك', 'code' => 'AS-BR'],
                    ['name' => 'Bareq', 'name_ar' => 'بارق', 'code' => 'AS-BQ2'],
                ],
            ],
            [
                'code' => 'TB',
                'name' => 'Tabuk',
                'name_ar' => 'تبوك',
                'counties' => [
                    ['name' => 'Tabuk', 'name_ar' => 'تبوك', 'code' => 'TB-TB'],
                    ['name' => 'Al-Wajh', 'name_ar' => 'الوجه', 'code' => 'TB-WJ'],
                    ['name' => 'Duba', 'name_ar' => 'ضباء', 'code' => 'TB-DB'],
                    ['name' => 'Tayma', 'name_ar' => 'تيماء', 'code' => 'TB-TY'],
                    ['name' => 'Umluj', 'name_ar' => 'أملج', 'code' => 'TB-UM'],
                    ['name' => 'Haql', 'name_ar' => 'حقل', 'code' => 'TB-HQ'],
                ],
            ],
            [
                'code' => 'HL',
                'name' => 'Hail',
                'name_ar' => 'حائل',
                'counties' => [
                    ['name' => 'Hail', 'name_ar' => 'حائل', 'code' => 'HL-HL'],
                    ['name' => 'Baqaa', 'name_ar' => 'بقعاء', 'code' => 'HL-BQ'],
                    ['name' => 'Al-Ghazalah', 'name_ar' => 'الغزالة', 'code' => 'HL-GH'],
                    ['name' => 'Ash-Shinan', 'name_ar' => 'الشنان', 'code' => 'HL-SH'],
                    ['name' => 'Al-Hait', 'name_ar' => 'الحائط', 'code' => 'HL-HT'],
                    ['name' => 'Samira', 'name_ar' => 'سميراء', 'code' => 'HL-SM'],
                    ['name' => 'Mawqaq', 'name_ar' => 'موقق', 'code' => 'HL-MQ'],
                    ['name' => 'As-Sulaimi', 'name_ar' => 'السليمي', 'code' => 'HL-SL'],
                ],
            ],
            [
                'code' => 'NB',
                'name' => 'Northern Borders',
                'name_ar' => 'الحدود الشمالية',
                'counties' => [
                    ['name' => 'Arar', 'name_ar' => 'عرعر', 'code' => 'NB-AR'],
                    ['name' => 'Rafha', 'name_ar' => 'رفحاء', 'code' => 'NB-RF'],
                    ['name' => 'Turaif', 'name_ar' => 'طريف', 'code' => 'NB-TR'],
                ],
            ],
            [
                'code' => 'JZ',
                'name' => 'Jazan',
                'name_ar' => 'جازان',
                'counties' => [
                    ['name' => 'Jazan', 'name_ar' => 'جازان', 'code' => 'JZ-JZ'],
                    ['name' => 'Sabya', 'name_ar' => 'صبيا', 'code' => 'JZ-SB'],
                    ['name' => 'Abu Arish', 'name_ar' => 'أبو عريش', 'code' => 'JZ-AA'],
                    ['name' => 'Samtah', 'name_ar' => 'صامطة', 'code' => 'JZ-SM'],
                    ['name' => 'Al-Harth', 'name_ar' => 'الحرث', 'code' => 'JZ-HR'],
                    ['name' => 'Damad', 'name_ar' => 'ضمد', 'code' => 'JZ-DM'],
                    ['name' => 'Al-Raith', 'name_ar' => 'الريث', 'code' => 'JZ-RT'],
                    ['name' => 'Farasan', 'name_ar' => 'فرسان', 'code' => 'JZ-FR'],
                    ['name' => 'Al-Aidabi', 'name_ar' => 'العيدابي', 'code' => 'JZ-AD'],
                    ['name' => 'Al-Darb', 'name_ar' => 'الدرب', 'code' => 'JZ-DR'],
                    ['name' => 'Baysh', 'name_ar' => 'بيش', 'code' => 'JZ-BY'],
                    ['name' => 'Ahad Al-Masarihah', 'name_ar' => 'أحد المسارحة', 'code' => 'JZ-AM'],
                    ['name' => 'Al-Tuwal', 'name_ar' => 'الطوال', 'code' => 'JZ-TW'],
                ],
            ],
            [
                'code' => 'NJ',
                'name' => 'Najran',
                'name_ar' => 'نجران',
                'counties' => [
                    ['name' => 'Najran', 'name_ar' => 'نجران', 'code' => 'NJ-NJ'],
                    ['name' => 'Sharourah', 'name_ar' => 'شرورة', 'code' => 'NJ-SH'],
                    ['name' => 'Hubuna', 'name_ar' => 'حبونا', 'code' => 'NJ-HB'],
                    ['name' => 'Badr Al-Janoub', 'name_ar' => 'بدر الجنوب', 'code' => 'NJ-BJ'],
                    ['name' => 'Yadamah', 'name_ar' => 'يدمة', 'code' => 'NJ-YD'],
                    ['name' => 'Thar', 'name_ar' => 'ثار', 'code' => 'NJ-TH'],
                    ['name' => 'Khbash', 'name_ar' => 'خباش', 'code' => 'NJ-KH'],
                ],
            ],
            [
                'code' => 'BH',
                'name' => 'Al-Baha',
                'name_ar' => 'الباحة',
                'counties' => [
                    ['name' => 'Al-Baha', 'name_ar' => 'الباحة', 'code' => 'BH-BH'],
                    ['name' => 'Baljurashi', 'name_ar' => 'بلجرشي', 'code' => 'BH-BJ'],
                    ['name' => 'Al-Mandaq', 'name_ar' => 'المندق', 'code' => 'BH-MN'],
                    ['name' => 'Al-Makhwah', 'name_ar' => 'المخواة', 'code' => 'BH-MK'],
                    ['name' => 'Al-Aqiq', 'name_ar' => 'العقيق', 'code' => 'BH-AQ'],
                    ['name' => 'Qilwa', 'name_ar' => 'قلوة', 'code' => 'BH-QL'],
                ],
            ],
            [
                'code' => 'JF',
                'name' => 'Al-Jawf',
                'name_ar' => 'الجوف',
                'counties' => [
                    ['name' => 'Sakaka', 'name_ar' => 'سكاكا', 'code' => 'JF-SK'],
                    ['name' => 'Dawmat Al-Jandal', 'name_ar' => 'دومة الجندل', 'code' => 'JF-DJ'],
                    ['name' => 'Al-Qurayyat', 'name_ar' => 'القريات', 'code' => 'JF-QR'],
                ],
            ],
        ];

        foreach ($regions as $regionData) {
            $regionId = Str::uuid()->toString();

            DB::table('regions')->insert([
                'id' => $regionId,
                'code' => $regionData['code'],
                'name' => $regionData['name'],
                'name_ar' => $regionData['name_ar'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($regionData['counties'] as $county) {
                DB::table('counties')->insert([
                    'id' => Str::uuid()->toString(),
                    'region_id' => $regionId,
                    'name' => $county['name'],
                    'name_ar' => $county['name_ar'],
                    'code' => $county['code'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
