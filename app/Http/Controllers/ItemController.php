<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class ItemController extends Controller
{
    public function show(string $id)
    {
        // ข้อมูลทดสอบ — ในอนาคตเปลี่ยนเป็นดึงจาก DB ด้วย Item::findOrFail($id)
        $item = [
            'id'          => (int) $id,
            'title'       => 'ผลของการใช้กลยุทธ์การเรียนรู้แบบร่วมมือที่มีต่อผลสัมฤทธิ์ทางการเรียนวิชาคณิตศาสตร์ของนักเรียนชั้นมัธยมศึกษาปีที่ 3',
            'title_en'    => 'Effects of Cooperative Learning Strategies on Mathematics Achievement of Grade 9 Students',
            'authors'     => ['ศิริลักษณ์ วงศ์ประเสริฐ'],
            'advisors'    => ['รศ.ดร.สมชาย ประทุมมาศ', 'ผศ.ดร.กัลยา วานิชวัฒนา'],
            'faculty'     => 'คณะศึกษาศาสตร์',
            'department'  => 'สาขาวิชาหลักสูตรและการสอน',
            'year'        => 2567,
            'type'        => 'วิทยานิพนธ์ปริญญาโท',
            'language'    => 'ภาษาไทย',
            'abstract'    => 'การวิจัยครั้งนี้มีวัตถุประสงค์เพื่อ 1) ศึกษาผลของการใช้กลยุทธ์การเรียนรู้แบบร่วมมือที่มีต่อผลสัมฤทธิ์ทางการเรียนวิชาคณิตศาสตร์ของนักเรียนชั้นมัธยมศึกษาปีที่ 3 และ 2) ศึกษาความพึงพอใจของนักเรียนที่มีต่อการจัดการเรียนรู้แบบร่วมมือ กลุ่มตัวอย่างเป็นนักเรียนชั้นมัธยมศึกษาปีที่ 3 โรงเรียนในสังกัดสำนักงานเขตพื้นที่การศึกษามัธยมศึกษา จังหวัดมหาสารคาม ภาคเรียนที่ 1 ปีการศึกษา 2567 จำนวน 60 คน',
            'abstract_en' => 'This research aimed to 1) study the effects of cooperative learning strategies on mathematics achievement of Grade 9 students, and 2) investigate students\' satisfaction with cooperative learning. The sample consisted of 60 Grade 9 students from schools under the Secondary Educational Service Area Office in Mahasarakham Province.',
            'keywords'    => ['การเรียนรู้แบบร่วมมือ', 'ผลสัมฤทธิ์ทางการเรียน', 'คณิตศาสตร์', 'มัธยมศึกษา', 'Cooperative Learning'],
            'doi'         => '10.14456/msuir.2567.001',
            'views'       => 1205,
            'downloads'   => 234,
            'files'       => [
                ['id' => 1, 'name' => 'THESIS_SIRILAK_2567.pdf',   'size' => '4.2 MB', 'pages' => 182, 'type' => 'pdf', 'restricted' => false],
                ['id' => 2, 'name' => 'ABSTRACT_SIRILAK_2567.pdf', 'size' => '0.5 MB', 'pages' => 4,   'type' => 'pdf', 'restricted' => false],
                ['id' => 3, 'name' => 'APPENDIX_SIRILAK_2567.pdf', 'size' => '1.8 MB', 'pages' => 45,  'type' => 'pdf', 'restricted' => true],
            ],
            'category'    => ['name' => 'MSU e-Theses', 'slug' => 'theses'],
        ];

        $relatedItems = [
            ['id' => 2, 'title' => 'การพัฒนาชุดกิจกรรมการเรียนรู้แบบโครงงานเพื่อส่งเสริมทักษะการคิดเชิงสร้างสรรค์',          'author' => 'ธนกร สุวรรณรัตน์',  'year' => 2567, 'type' => 'วิทยานิพนธ์ปริญญาโท'],
            ['id' => 3, 'title' => 'ผลการใช้รูปแบบการสอนแบบสืบเสาะหาความรู้ต่อผลสัมฤทธิ์ทางการเรียนวิทยาศาสตร์',              'author' => 'อรุณี ภูมิไพศาล',    'year' => 2566, 'type' => 'วิทยานิพนธ์ปริญญาโท'],
            ['id' => 4, 'title' => 'การพัฒนาทักษะการคิดวิจารณญาณของนักเรียนผ่านกิจกรรมการเรียนรู้เชิงรุก',                     'author' => 'มนัสชัย เรืองศรี',   'year' => 2566, 'type' => 'ดุษฎีนิพนธ์ปริญญาเอก'],
            ['id' => 5, 'title' => 'ประสิทธิผลของสื่อดิจิทัลเชิงโต้ตอบในการเรียนการสอนระดับมัธยมศึกษา',                        'author' => 'จินตนา พลอยสุข',    'year' => 2565, 'type' => 'วิทยานิพนธ์ปริญญาโท'],
        ];

        return Inertia::render('Item', [
            'item'         => $item,
            'relatedItems' => $relatedItems,
        ]);
    }
}
