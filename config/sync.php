<?php

return [

    /*
    |--------------------------------------------------------------------------
    | هوية الفرع (Branch Identity)
    |--------------------------------------------------------------------------
    |
    | branch_id: معرّف ثابت لهذا التثبيت. على السيرفر المركزي اضبطه = 'server'.
    | على كل جهاز فرع اضبط معرّفاً فريداً (أو اتركه ليُولَّد تلقائياً عند أول تشغيل).
    |
    | branch_code: بادئة قصيرة (A, B, C...) تُستخدم في ترقيم الفواتير لمنع التصادم.
    |
    */
    'branch_id'   => env('BRANCH_ID'),
    'branch_code' => env('BRANCH_CODE'),

    // معرّف الفرع الذي يمثّل السيرفر المركزي (master).
    'server_branch_id' => env('SERVER_BRANCH_ID', 'server'),

    /*
    |--------------------------------------------------------------------------
    | المزامنة (Sync) — تُستخدم في مراحل لاحقة (P2)
    |--------------------------------------------------------------------------
    |
    | enabled: تفعيل المزامنة. interval: الدورية بالثواني للمزامنة التلقائية.
    | server_url: عنوان الـ Sync API على السيرفر المركزي.
    |
    */
    'enabled'    => env('SYNC_ENABLED', false),
    'interval'   => (int) env('SYNC_INTERVAL', 60),
    'server_url' => env('SYNC_SERVER_URL'),

    // المفتاح السرّي المشترك للمصادقة بين الفرع والسيرفر (X-Sync-Token).
    'token' => env('SYNC_TOKEN'),

    // حجم الدفعة عند الرفع (عدد الصفوف لكل جدول في الطلب الواحد).
    'batch_size' => (int) env('SYNC_BATCH_SIZE', 200),

    // رابط تحميل تطبيق سطح المكتب (صفحة آخر إصدار على GitHub Releases).
    // يظهر زر التحميل في نسخة الويب فقط عند ضبط هذا الرابط (وليس داخل تطبيق الديسكتوب).
    'desktop_download_url' => env('DESKTOP_DOWNLOAD_URL'),

    /*
    |--------------------------------------------------------------------------
    | سجل الجداول القابلة للمزامنة (Registry) وترتيبها
    |--------------------------------------------------------------------------
    |
    | هذا السجل الكامل لكل جدول قابل للمزامنة (يربط اسم الجدول بالـ Model). يُستخدم:
    |   - كقائمة بيضاء للجداول المدعومة في SyncRepository::applyBatch (على الطرفين).
    |   - لحلّ Model class + ترتيب التبعيات عند الـ push (انظر 'push' أعلاه).
    | الترتيب مهم: الجداول الأب قبل الأبناء حتى تتوفّر مفاتيح uuid المرجعية قبل
    | وصول الصفوف التابعة. اتجاه كل جدول (push/pull) محدَّد في 'push' و'pull'.
    |
    */
    'models' => [
        'users'                   => \App\Models\User::class,
        'drugs'                   => \App\Models\Drug::class,
        'products'                => \App\Models\Product::class,
        'suppliers'               => \App\Models\Supplier::class,
        'customers'               => \App\Models\Customer::class,
        'employees'               => \App\Models\Employee::class,
        'expenses'                => \App\Models\Expense::class,
        'employee_transactions'   => \App\Models\EmployeeTransaction::class,
        'user_drug_inventory'     => \App\Models\UserDrugInventory::class,
        'sales'                   => \App\Models\Sale::class,
        'sale_items'              => \App\Models\SaleItem::class,
        'sale_payments'           => \App\Models\SalePayment::class,
        'sale_returns'            => \App\Models\SaleReturn::class,
        'sale_return_items'       => \App\Models\SaleReturnItem::class,
        'purchase_invoices'       => \App\Models\PurchaseInvoice::class,
        'purchase_invoice_items'  => \App\Models\PurchaseInvoiceItem::class,
        'purchase_payments'       => \App\Models\PurchasePayment::class,
        'purchase_returns'        => \App\Models\PurchaseReturn::class,
        'purchase_return_items'   => \App\Models\PurchaseReturnItem::class,
        'pending_orders'          => \App\Models\PendingOrder::class,
        'pending_order_items'     => \App\Models\PendingOrderItem::class,
        'drawer_locks'            => \App\Models\DrawerLock::class,
        'sub_users'               => \App\Models\SubUser::class,
        'notifications'           => \App\Models\Notification::class,
        'ads'                     => \App\Models\Ad::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | الجداول التي يدفعها الفرع للسيرفر (Push) — الفرع master لها
    |--------------------------------------------------------------------------
    |
    | قرار الاتجاه (direction-based conflict): البيانات التشغيلية الخاصة بالفرع
    | (مبيعات/مشتريات/مخزون/مصروفات...) يملكها الفرع ويدفعها للسيرفر فقط. الكتالوج
    | المركزي (drugs, ads) لا يُدفع — السيرفر master له ويُسحب عبر 'pull' أدناه. هذا
    | يلغي التعارض ثنائي الاتجاه على الكتالوج من الأساس.
    |
    | ملاحظة: 'models' أدناه يبقى السجل الكامل (registry) لأنه يُستخدم أيضاً كقائمة
    | بيضاء للجداول المدعومة في SyncRepository::applyBatch على الطرفين (بما فيها ما
    | يُسحب). لذا نفصل ما يُدفع في هذه القائمة، بنفس ترتيب التبعيات (الأب قبل الابن).
    |
    | سعر/مخزون الفرع الخاص يعيش في user_drug_inventory (يُدفع) — منفصل عن كتالوج
    | drugs المركزي (يُسحب).
    |
    */
    'push' => [
        'products',
        'suppliers',
        'customers',
        'employees',
        'expenses',
        'employee_transactions',
        'user_drug_inventory',
        'sales',
        'sale_items',
        'sale_payments',
        'sale_returns',
        'sale_return_items',
        'purchase_invoices',
        'purchase_invoice_items',
        'purchase_payments',
        'purchase_returns',
        'purchase_return_items',
        'pending_orders',
        'pending_order_items',
        'drawer_locks',
        'notifications',
    ],

    /*
    |--------------------------------------------------------------------------
    | روابط المفاتيح الأجنبية (FK → الجدول المرجعي)
    |--------------------------------------------------------------------------
    |
    | الـ FK محلياً يشير إلى id محلي، لكن id يختلف بين الفرع والسيرفر. لذا عند الرفع
    | نترجم كل FK إلى uuid الصف المرجعي، والسيرفر يترجمه ثانية إلى id لديه.
    | الصيغة: 'table' => ['fk_column' => 'referenced_table', ...].
    |
    | نُدرج فقط الـ FK التي تشير إلى جداول قابلة للمزامنة. مفاتيح user_id تُترك كما هي
    | (المستخدمون يُزامَنون بآلية منفصلة server-master، وهي ثابتة عبر الأجهزة).
    |
    */
    'relations' => [
        'sale_items'             => ['sale_id' => 'sales', 'drug_id' => 'drugs'],
        'sale_payments'          => ['sale_id' => 'sales', 'customer_id' => 'customers'],
        'sale_returns'           => ['sale_id' => 'sales', 'customer_id' => 'customers'],
        'sale_return_items'      => ['sale_return_id' => 'sale_returns', 'sale_item_id' => 'sale_items', 'drug_id' => 'drugs'],
        'sales'                  => ['customer_id' => 'customers'],
        'purchase_invoices'      => ['supplier_id' => 'suppliers'],
        'purchase_invoice_items' => ['purchase_invoice_id' => 'purchase_invoices', 'product_id' => 'products', 'drug_id' => 'drugs'],
        'purchase_payments'      => ['purchase_invoice_id' => 'purchase_invoices', 'supplier_id' => 'suppliers'],
        'purchase_returns'       => ['purchase_invoice_id' => 'purchase_invoices', 'supplier_id' => 'suppliers'],
        'purchase_return_items'  => ['purchase_return_id' => 'purchase_returns', 'purchase_invoice_item_id' => 'purchase_invoice_items', 'drug_id' => 'drugs'],
        'pending_orders'         => ['customer_id' => 'customers'],
        'pending_order_items'    => ['pending_order_id' => 'pending_orders', 'drug_id' => 'drugs'],
        'employee_transactions'  => ['employee_id' => 'employees', 'expense_id' => 'expenses'],
        'user_drug_inventory'    => ['drug_id' => 'drugs'],
        // sub_users تُسحب server→branch؛ owner_id يُترجم عبر uuid المستخدم المالك.
        'sub_users'              => ['owner_id' => 'users'],
    ],

    /*
    |--------------------------------------------------------------------------
    | الجداول التي يسحبها الفرع من السيرفر (Pull) — السيرفر master لها
    |--------------------------------------------------------------------------
    |
    | الكتالوج والمراجع المركزية التي يوزّعها السيرفر على كل الفروع (الأدوية،
    | الإعلانات). البيانات الخاصة بالفرع (مبيعات/مشتريات/مخزون الفرع) لا تُسحب —
    | فهي تُدفع من الفرع للسيرفر فقط (انظر push أعلاه). الترتيب: الأب قبل الابن.
    |
    | حسابات الفرع (offline login): users + sub_users تُسحب server→branch لكن **مُخصَّصة
    | للفرع** (فقط الصيدلية المالكة + موظفيها، عبر branches.user_id) — لا تُسحب كل الجداول.
    | الترتيب: users قبل sub_users (owner_id يُترجم عبر uuid المستخدم).
    |
    | عند تعارض uuid أثناء السحب: السيرفر يكسب (السيرفر master للكتالوج والحسابات).
    |
    */
    'pull' => [
        'users',
        'drugs',
        'ads',
        'sub_users',
    ],

    /*
    |--------------------------------------------------------------------------
    | جداول تُحفظ بنفس id السيرفر على الفرع (preserve id)
    |--------------------------------------------------------------------------
    |
    | users فقط: الفرع يتبع صيدلية (tenant) واحدة، فنحفظ صف المستخدم بنفس users.id
    | الموجود على السيرفر بدل توليد id محلي جديد. هكذا يصبح Auth::id() على الفرع
    | مطابقاً لـ id السيرفر، فتتطابق كل أعمدة user_id في الجداول المدفوعة (sales,
    | products, customers...) تلقائياً دون الحاجة لترجمة user_id في 13 جدولاً.
    | يُطبَّق فقط عند السحب على الفرع (users لا تُدفع، فلا يؤثر على السيرفر).
    |
    */
    'preserve_id' => [
        'users',
    ],

];
