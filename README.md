# ASW Register

ปลั๊กอิน WordPress สำหรับรับลีด (lead) หลายฟอร์ม พร้อมเก็บ UTM, ส่ง API ภายนอก, อีเมลแจ้งผู้กรอก และส่งออก Leads เป็น Excel/CSV

- **Requires:** WordPress 6.2+, PHP 7.4+
- **Text domain:** `asw-register`

## ความสามารถหลัก

- สร้าง/แก้ไขฟอร์มได้หลายชุดจากเมนู **ASW Register** ในแอดมิน
- ฟิลด์มาตรฐาน + ฟิลด์กำหนดเอง (ประเภท text, email, tel, textarea, select, radio, checkbox)
- เก็บ UTM / gclid / HandL ผ่านฟิลด์ซ่อน (เติมค่าฝั่งเบราว์เซอร์)
- ส่งข้อมูลไป REST/AJAX ภายนอก (ตั้ง endpoint, method, headers ได้)
- ส่งอีเมลขอบคุณหลังส่งฟอร์ม (merge tags)
- รายการ Leads + กรองตามฟอร์ม + ส่งออก (ต้องมี `vendor/` จาก Composer สำหรับ Excel)
- ฝั่งหน้าเว็บใช้ **Tailwind CSS v4.2** (prefix `aswr:`) สำหรับ UI ฟอร์ม — build เป็นไฟล์ CSS คงที่

## ติดตั้งบน host (อัปโหลดแล้วใช้ได้เลย)

แนวทางที่เหมาะกับการ zip โฟลเดอร์ปลั๊กอินแล้วอัปโหลด **โดยไม่รัน Composer / Node บนเซิร์ฟเวอร์**

1. อัปโหลดโฟลเดอร์ `asw-register` ไปที่ `wp-content/plugins/`
2. ตรวจว่ามี **`vendor/`** (รวม `vendor/autoload.php`) อยู่ในแพ็กเกจที่ deploy
3. ตรวจว่ามี **`assets/build/asw-reg-form-tw.css`** เป็นเวอร์ชันล่าสุด (ไฟล์นี้ได้จาก `npm run build:tw` ตอนพัฒนา)
4. เปิดใช้งานปลั๊กอินใน **ปลั๊กอิน** — ตารางฐานข้อมูลจะถูกสร้างตอน activate

ถ้าขาด `vendor/` ฟีเจอร์ส่งออก Excel จะแจ้งให้รัน `composer install` แทน

## พัฒนาในเครื่อง local

### Composer (PhpSpreadsheet)

```bash
cd wp-content/plugins/asw-register
composer install --no-dev --optimize-autoloader
```

- เก็บ **`composer.lock`** ใน git
- สำหรับ deploy แบบ “อัปแล้วใช้ได้เลย” ให้ **commit `vendor/`** และ **อย่า** ใส่ `vendor/` ใน `.gitignore`

### Tailwind v4.2 (สไตล์ฟอร์มหน้าเว็บ)

```bash
cd wp-content/plugins/asw-register
npm install
npm run build:tw
```

- แก้สไตล์/utility ที่ `src/tw-form.css` และ/หรือคลาส `aswr:*` ใน `templates/form-template.php` แล้วรัน `build:tw` อีกครั้ง
- **`node_modules/`** อยู่ใน `.gitignore` — บน host ไม่จำเป็นต้องมี Node
- **ควร commit** ไฟล์ `assets/build/asw-reg-form-tw.css` หลัง build เพื่อให้ production ตรงกับ UI ล่าสุด

คำสั่ง watch ระหว่างแก้ UI:

```bash
npm run watch:tw
```

## การใช้งานในเว็บไซต์

### Shortcode

แทรกในหน้า/โพสต์ (หรือบล็อก):

```text
[asw_register_form id="123"]
```

แทน `123` ด้วย ID ฟอร์มจากหน้าแอดมิน (มีข้อความบอก ID ตอนแก้ฟอร์ม)

ตัวเลือก class เพิ่มเติม:

```text
[asw_register_form id="123" class="my-extra-class"]
```

### Inject ลงหน้าโปรเจกต์ (#register_form)

ในแต่ละฟอร์ม (แท็บ **General**) เลือก **Inject into page** จากรายการโพสต์ประเภท **condominium** / **house** ที่ไม่อยู่ในหมวด `thank-you` และไม่ถูกกรองตามกฎ title ภายในโค้ด — ปลั๊กอินจะแสดงฟอร์มในแองเคอร์ `#register_form` ของธีมเมื่อเปิดหน้าโพสต์นั้น (ยังใช้ shortcode ที่อื่นได้ตามปกติ)

## โครงสร้างโฟลเดอร์ (สรุป)

| Path | รายละเอียด |
|------|-------------|
| `includes/` | ตรรกะหลัก: ฟอร์ม, leads, AJAX, shortcode, inject |
| `includes/admin/` | หน้าแอดมิน, บันทึกฟอร์ม, export |
| `templates/` | เทมเพลตฟอร์มหน้าเว็บ |
| `templates/admin/` | เทมเพลตแอดมิน |
| `assets/js/`, `assets/css/` | สคริปต์/สไตล์แอดมิน + JS ฟอร์มหน้าเว็บ |
| `assets/build/` | CSS ฟอร์มที่ build จาก Tailwind |
| `src/tw-form.css` | entry ของ Tailwind v4 |
| `vendor/` | แพ็กเกจ Composer (PhpSpreadsheet ฯลฯ) |

## ฐานข้อมูล

- `wp_asw_register_forms` — เก็บคอนฟิกฟอร์ม (รวม `inject_post_id` สำหรับหน้า inject)
- `wp_tt_leads` — เก็บลีด

เวอร์ชันสคีมาเก็บที่ option `asw_reg_db_version` — อัปเดตปลั๊กอินจะรัน migration ผ่าน `dbDelta` ตอนเข้าแอดมินเมื่อเวอร์ชันไม่ตรง

## การถอนติดตั้ง

ใน **Settings** ของปลั๊กอินมีตัวเลือกลบข้อมูลตอนถอน — ถ้าเปิด ระบบจะลบตารางและ options ที่เกี่ยวข้องเมื่อถอนปลั๊กอิน (ดู `uninstall.php`)

## เครดิต

- Author: AssetWise PLC
- Plugin URI: https://assetwise.co.th
