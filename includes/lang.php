<?php
// Language bootstrap - placed after session_start()
// Supports: en (English), am (ማርኛ/Amharic), om (Afaan Oromoo)
$curr_lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'en';
if(isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'am', 'om'], true)) {
    $curr_lang = $_GET['lang'];
    $_SESSION['lang'] = $curr_lang;
}
$lang = in_array($curr_lang, ['en', 'am', 'om'], true) ? $curr_lang : 'en';

function lang_name($code) {
    if($code === 'am') return 'አማርኛ';
    if($code === 'om') return 'Afaan Oromoo';
    return 'English';
}

function lang_next($current) {
    $order = ['en', 'am', 'om'];
    $i = array_search($current, $order, true);
    if($i === false) return 'am';
    return $order[($i + 1) % 3];
}

$T = [
    'back_home' => ['Back to Home', 'ወደ መነሻ ተመለስ', 'Gara mana deebi\'i'],
    'login_title' => ['Welcome Back', 'እንኳን ደህና መጡ', 'Baga nagaan deebitan'],
    'login_subtitle' => ['Sign in to access your landlord dashboard.', 'ወደ የቤት አከራይ ዳሽቦርድዎ ለመግባት ይግቡ።', 'Gara dashboordii abbaa mana kireessaa keessan seensaa.'],
    'sign_in' => ['Sign In', 'ግባ', 'Seensaa'],
    'email_address' => ['Email Address', 'የኢሜይል አድራሻ', 'Teessoo Imaayelii'],
    'password' => ['Password', 'የይለፍ ቃል', 'Jecha Iccitii'],
    'pw_placeholder' => ['Enter your password', 'የይለፍ ቃልዎን ያስገቡ', 'Jecha iccitii keessan seensaa'],
    'pw_placeholder_signup' => ['Create a strong password', 'ጠንካራ የይለፍ ቃል ይፍጠሩ', 'Jecha iccitii jabaa uumaa'],
    'remember_me' => ['Remember me', 'አስታውሰኝ', 'Na yaadadhu'],
    'forgot_password' => ['Forgot password?', 'የይለፍ ቃል ረስተዋል?', 'Jecha iccitii dagattee?'],
    'resend_verify_link' => ['Resend verification email', 'የማረጋገጫ ኢሜይል እንደገና ላክ', 'Imaayelii mirkaneeffannaa deebi\'i ergi'],
    'pw_too_short' => ['Password must be at least 6 characters.', 'የይለፍ ቃል ቢያንስ 6 ቁምፊዎች መሆን አለበት።', 'Jecha iccitii, xiqqaattis warnaa 6 ta\'uu qaba.'],

    // Forgot / reset password
    'fp_title' => ['Forgot Password', 'የይለፍ ቃል ረስተዋል?', 'Jecha iccitii dagattanii?'],
    'fp_subtitle' => ['Enter your account email and we\'ll send you a link to reset your password.', 'የመለያዎ ኢሜይል ያስገቡ፤ የይለፍ ቃልዎን ለመቀየር አገናኝ እንልክልዎታለን።', 'Imaayelii akkaawuntii keessanii seensaa; jecha iccitii keessan haaromsuuf liinkii isiniif ergina.'],
    'fp_btn' => ['Send Reset Link', 'የመልሶ ማግኛ አገናኝ ላክ', 'Liinkii haaromsaa ergi'],
    'fp_sent' => ['If an account exists for that email, a password reset link has been sent. Check your inbox.', 'ለመለያ ካለ፣ በዚያ ኢሜይል የይለፍ ቃል የመቀየር አገናኝ ተልኳል። የመልእክት ሳጥንዎን ይፈትሹ።', 'Akkaawuntiin yoo jiraate, liinkii jijjiirrannaa jecha iccitii imaayelii sanaan ergameera. Imaayelii keessan ilaalaa.'],
    'fp_invalid_email' => ['Please enter a valid email address.', 'እባክዎ ትክክለኛ የኢሜይል አድራሻ ያስገቡ።', 'Kadhattee teessoo imaayelii sirrii seensaa.'],
    'fp_back' => ['Back to Sign In', 'ወደ መግቢያ ተመለስ', 'Gara Seensaatti deebi\'i'],
    'rp_title' => ['Set a New Password', 'አዲስ የይለፍ ቃል ያስገቡ', 'Jecha Iccitii Haaraa Hirkisaa'],
    'rp_subtitle' => ['Choose a new password for your AdamaRent account.', 'ለAdamaRent መለያዎ አዲስ የይለፍ ቃል ይምረጡ።', 'Akkaawuntii AdamaRent keessaniif jecha iccitii haaraa filadhaa.'],
    'rp_new_pw_label' => ['New Password', 'አዲስ የይለፍ ቃል', 'Jecha Iccitii Haaraa'],
    'rp_new_pw_ph' => ['Enter a new password (min 6 characters)', 'አዲስ የይለፍ ቃል ያስገቡ (ቢያንስ 6 ቁምፊ)', 'Jecha iccitii haaraa seensaa (warnaa 6 xiqqaattis)'],
    'rp_btn' => ['Reset Password', 'የይለፍ ቃል ቀይር', 'Jecha Iccitii Haaromsi'],
    'rp_invalid' => ['This reset link is invalid. Check the link in your email or request a new one.', 'ይህ የመልሶ ማግኛ አገናኝ የተሳሳተ ነው። እባክዎ አዲስ ይጠይቁ።', 'Liinkii haaromsaa kuni sirrii miti. Liinkii imaayelii keessan ilaalaa ykn haaraa gaafadhaa.'],
    'rp_expired' => ['This reset link has expired. Please request a new one.', 'ይህ የመልሶ ማግኛ አገናኝ ጊዜው አልፏል። እባክዎ አዲስ ይጠይቁ።', 'Liinkii haaromsaa kuni meegaa darbe. Kadhattee haaraa gaafadhaa.'],
    'rp_back' => ['Back to Sign In', 'ወደ መግቢያ ተመለስ', 'Gara Seensaatti deebi\'i'],
    'or_continue' => ['or continue with', 'ወይም በሌላ ቀጥል', 'yookiin itti fufi'],
    'continue_google' => ['Continue with Google', 'በGoogle ቀጥል', 'Google\'idhaan itti fufi'],
    'no_account' => ['Don\'t have an account?', 'መለያ የለዎትም?', 'Akkaawuntii hin qabdanii?'],
    'create_one' => ['Create one', 'አዲስ ይፍጠሩ', 'Tokko uumaa'],
    'secure_note' => ['Your information is encrypted and never shared.', 'የእርስዎ መረጃ ተመስጥሮ ተጠብቆ ይቆያል፤ ከማንም ጋር አይጋራም።', 'Odeeffannoon keessan isiirameeti ykn eenyumaan hin qoodamu.'],

    // Left panel (shared)
    'eyebrow' => ['Adama City\'s Rental Marketplace', 'የአዳማ ከተማ የኪራይ ገበያ', 'Gabaa Kireeffama Magaalaa Adaamaa'],
    'login_left_title1' => ['Welcome back, ', 'እንኳን ደህና መጡ፣ ', 'Baga nagaan deebitan, '],
    'login_left_title2' => ['your listings', 'ማስታወቂያዎችዎ', 'baallannoonni keessan'],
    'login_left_title3' => [' are waiting', ' ይጠብቁዎታል', ' isin eeggachaa jiru'],
    'login_left_desc' => ['Sign in to manage your properties, respond to tenant requests, and keep your rentals in front of the right people.', 'ንብረቶችዎን ለማስተዳደር፣ የተከራዮች ጥያቄዎችን ለመመለስ እና ኪራዩን ለትክክለኛዎቹ ሰዎች ለማሳየት ይግቡ።', 'Qabeenya keessan bulchuu, gaaffii kireessitootaa deebisuu, fi kireessuu keessan namoota sirrii duraa qabaachuuf seensaa.'],
    'feat_mng' => ['Manage your properties', 'ንብረቶችዎን ያስተዳድሩ', 'Qabeenya keessan bulchaa'],
    'feat_mng_s' => ['Post, edit, and track listings in one place.', 'ማስታወቂያዎችን በአንድ ቦታ ይለጥፉ፣ ያርትዑና ይከታተሉ።', 'Baallannoo bakka tokkotti baachaa, jijjiirraa, fi hordofaa.'],
    'feat_reach' => ['Reach local tenants', 'በአካባቢው ያሉ ተከራዮችን ያግኙ', 'Kireessitoota naannoo keessan argachaa'],
    'feat_reach_s' => ['Connect with people actively searching right now.', 'በአሁኑ ጊዜ እየፈለጉ ከሚገኙ ሰዎች ጋር ይገናኙ።', 'Namoota amma barbaaduu jalqabaniin wal quunnamaa.'],
    'feat_secure' => ['Secure and private', 'ደህንነቱ የተጠበቀ እና የግል', 'Nageenya qabeessa fi dhuunfaa'],
    'feat_secure_s' => ['Your account data is always protected.', 'የመለያዎ መረጃ ሁልጊዜ የተጠበቀ ነው።', 'Odeeffannoon akkaawuntii keessan yeroo hundaa eegamaadha.'],
    'feat_free' => ['Free to get started', 'ለመጀመር ነፃ', 'Jalqabuuf bilisa'],
    'feat_free_s' => ['No upfront listing fees, ever.', 'ምንም የማስታወቂያ ክፍያ የለም።', 'Mindaa baallannoo duraa hin jiru.'],
    'feat_unlimited' => ['List unlimited properties', 'ያልተገደበ ንብረት ይለጥፉ', 'Qabeenya daangaa malee baalladhaa'],
    'feat_unlimited_s' => ['Showcase every home or space you manage.', 'የሚያስተዳድሯቸውን ቤቶች ሁሉ ያሳዩ።', 'Mana yookaan bakka bulchitan isaan hunda agarsiisaa.'],
    'quote_text' => ['"Found a reliable tenant within a week of posting. AdamaRent made everything simple."', '"ማስታወቂያ ከለጠፍኩ በአንድ ሳምንት ውስጥ አስተማማኝ ተከራይ አገኘሁ። AdamaRent ሁሉንም ነገር ቀላል አደረገ።"', '"Baallannoon booda torban tokko keessatti kireessituu amanamaa arge. AdamaRent waan hunda salpheesse."'],
    'quote_author' => ['— Verified landlord, Adama', '— የተረጋገጠ ቤት አከራይ፣ አዳማ', '— Abbaa mana mirkaneeffame, Adaamaa'],

    // Register
    'register_title' => ['Create Account', 'መለያ ፍጠር', 'Akkaawuntii Uumaa'],
    'register_subtitle' => ['Join AdamaRent as a landlord. It takes less than a minute.', 'እንደ ቤት አከራይ AdamaRentን ይቀላቀሉ። ከአንድ ደቂቃ በታች ይወስዳል።', 'Akka abbaa manaatin AdamaRentitti makamaa. Daqiiqaa tokkoo gadiitti fudhata.'],
    'full_name' => ['Full Name', 'ሙሉ ስም', 'Maqaa Guutuu'],
    'full_name_ph' => ['Your full name', 'ሙሉ ስምዎ', 'Maqaa guutuu keessan'],
    'email_ph' => ['you@example.com', 'you@example.com', 'you@example.com'],
    'reg_btn' => ['Create Account', 'መለያ ፍጠር', 'Akkaawuntii Uumaa'],
    'admin_setup_key' => ['Admin Setup Key', 'የአስተዳዳሪ የማዋቀሪያ ቁልፍ', 'Furtuu Sirnaa Abbaa Taateessaa'],
    'admin_key_ph' => ['Paste admin setup key here', 'የአስተዳዳሪ የማዋቀሪያ ቁልፍ እዚህ ያስቀምጡ', 'Furtuu sirnaa abbaa taateessaa asitti gulufaa'],
    'admin_key_hint' => ['Only needed if you want this account to become the first administrator.', 'ይህ መለያ የመጀመሪያው አስተዳዳሪ እንዲሆን ከፈለጉ ብቻ ያስፈልጋል።', 'Akkaawuntii kun abbaa taateessaa jalqabaa ta\'u yoo barbaaddan qofa barbaachisa.'],
    'im_admin' => ['I\'m the site administrator', 'እኔ የጣቢያው አስተዳዳሪ ነኝ', 'Ani abbaa taateessaa weebsaayitii kanaati'],
    'agree_terms' => ['I agree to the ', 'ከ', 'Waliin waliigale: '],
    'agree_terms2' => [' and ', ' እና ከ', ' fi '],
    'terms_service' => ['Terms of Service', 'አገልግሎት ውሎች', 'Seera Tajaajilaa'],
    'privacy_policy' => ['Privacy Policy', 'ግላዊነት ፖሊሲ', 'Seera Iccitii'],
    'agree_terms_suffix' => ['', '', ''],
    'has_account' => ['Already have an account?', 'መለያ አለዎት?', 'Akkaawuntii qabdanii?'],
    'sign_in_link' => ['Sign in', 'ይግቡ', 'Seensaa'],
    'register_left_title1' => ['Your property, ', 'ንብረትዎ፣ ', 'Qabeenya keessan, '],
    'register_left_title2' => ['seen by thousands', 'በሺዎች የሚታይ', 'kireessitoota kumaan'],
    'register_left_title3' => [' of tenants', ' ተከራዮች', ' mul\'ata'],
    'register_left_desc' => ['Create your free landlord account and start listing your properties to potential tenants across Adama City.', 'የነፃ የቤት አከራይ መለያዎን ይፍጠሩ እና በአዳማ ከተማ ላሉ ተከራዮች ንብረትዎን መለጠፍ ይጀምሩ።', 'Akkaawuntii abbaa mana bilisaa uumaa; qabeenya keessan kireessitoota Magaalaa Adaamaa wajjin baallachuu jalqabaa.'],
    'secure_reg_note' => ['Your information is encrypted and never shared.', 'የእርስዎ መረጃ ተመስጥሮ ተጠብቆ ይቆያል፤ ከማንም ጋር አይጋራም።', 'Odeeffannoon keessan isiirameeti ykn eenyumaan hin qoodamu.'],

    // Home page
    'nav_home' => ['Home', 'መነሻ', 'Mana'],
    'nav_property_types' => ['Property types', 'የንብረት ዓይነቶች', 'Gosa Qabeenyaa'],
    'nav_how_it_works' => ['How it works', 'እንዴት እንደሚሰራ', 'Akkamitti Hojjeta'],
    'nav_contact' => ['Contact', 'አግኙን', 'Nu Quunnamaa'],
    'nav_login' => ['Login', 'ግባ', 'Seensaa'],
    'nav_post_house' => ['Post a Property', 'ንብረት ይለጥፉ', 'Qabeenya Baalladhaa'],
    'nav_dashboard' => ['Dashboard', 'ዳሽቦርድ', 'Dashboordii'],
    'nav_profile' => ['My Profile', 'መገለጫዬ', 'Piroofaayilaa koo'],
    'nav_signout' => ['Sign Out', 'ውጣ', 'Ba\'i'],
    'hero_title1' => ['Find Your Perfect ', 'በአዳማ ፍጹሙን ', 'Siif gaarii ta\'e '],
    'hero_title2' => ['Rental Home', 'የኪራይ ቤት', 'Mana Kireessaa'],
    'hero_title3' => [' in Adama', ' ያግኙ', ' Adaamaa keessatti argadhu'],
    'hero_desc' => ['The trusted digital marketplace connecting landlords and tenants across Adama City. Search, compare, and secure your next property.', 'በአዳማ ከተማ ቤት አከራዮችን እና ተከራዮችን የሚያገናኝ ታማኝ ዲጂታል ገበያ። ቀጣዩን ንብረትዎን ይፈልጉ፣ ያወዳድሩ እና ያስጠብቁ።', 'Gabaa dijitaalii amanamtuu abbootii manaati fi kireessitoota Magaalaa Adaamaa walitti qunnamsiisu. Qabeenya itti aanu keessan barbaadaa, wal bira qabaadhaa, fi milkiiseessaa.'],
    'btn_get_started' => ['Get Started', 'እንጀምር', 'Eegaluu'],
    'btn_list_property' => ['List Your Property', 'ንብረትዎን ይዘርዝሩ', 'Qabeenya Keessan Baalladhaa'],
    'stat_properties' => ['Properties Listed', 'የተዘረዘሩ ንብረቶች', 'Qabeenyi Baallaman'],
    'stat_landlords' => ['Verified Landlords', 'የተረጋገጡ ቤት አከራዮች', 'Abboonni Mana Mirkaneeffaman'],
    'stat_kebeles' => ['Kebeles Covered', 'የተሸፈኑ ቀበሌዎች', 'Hambalee Haguugaman'],
    'stat_tenants' => ['Happy Tenants', 'ደስተኛ ተከራዮች', 'Kireessitoota Gammaddan'],
    'cat_tag' => ['Property Types', 'የንብረት ዓይነቶች', 'Gosa Qabeenyaa'],
    'cat_heading' => ['Browse by Category', 'በምድብ ይፈልጉ', 'Gosaan Barbaadhaa'],
    'cat_sub' => ['From cozy rooms to commercial spaces, find exactly what you need.', 'ከምቹ ክፍሎች እስከ የንግድ ቦታዎች፣ የሚፈልጉትን በትክክል ያግኙ።', 'Kutaa mijatoo jalqabee hanga bakka daldalaatti, waan barbaaddan sirriitti argadhaa.'],
    'cat_single' => ['Single Homes', 'የግል ቤቶች', 'Manneen Dhuunfaa'],
    'cat_single_desc' => ['Cozy rooms and traditional houses perfect for students and working professionals.', 'ለተማሪዎች እና ባለሙያዎች ተስማሚ የሆኑ ምቹ ክፍሎች እና ባህላዊ ቤቶች።', 'Kutaa mijataa fi manneen aadaa barattoota fi hojjattootaaf mijatoo.'],
    'cat_apt' => ['Apartments & Villas', 'አፓርትመንቶች እና ቪላዎች', 'Apartmentii fi Villa'],
    'cat_apt_desc' => ['Modern apartments and luxury villas in prime locations across Adama.', 'በአዳማ ውስጥ በምርጥ ቦታዎች የሚገኙ ᘋመናዊ አፓርትመንቶች እና የቅንጦት ቪላዎች።', 'Apartmentii ammayyaa fi villa fooyya\'aa bakka Adaamaa filatamaniirratti argaman.'],
    'cat_com' => ['Commercial Spaces', 'የንግድ ቦታዎች', 'Bakka Daldalaa'],
    'cat_com_desc' => ['Offices, shops, and warehouses for businesses of all sizes.', 'ለሁሉም መጠን የንግድ ድርጅቶች ቢሮዎች፣ ሱቆች እና መጋዘኖች።', 'Waajjira, suuqii, fi madhala daldala guddina hundaaf.'],
    'hiw_tag' => ['How It Works', 'እንዴት እንደሚሰራ', 'Akkamitti Hojjeta'],
    'hiw_heading' => ['Simple as 1-2-3', 'ቀላል እንደ 1-2-3', 'Salphaa akka 1-2-3'],
    'step1_t' => ['Get Started', 'ይጀምሩ', 'Eegaluu'],
    'step1_d' => ['Browse listings by category, location, and price range to find your ideal property.', 'ተስማሚውን ንብረት ለማግኘት በምድብ፣ በቦታ እና በዋጋ ይፈልጉ።', 'Qabeenya filatama keessan argachuuf gosaan, bakkaan, fi daangaa gatidhaan baallannoo barbaadaa.'],
    'step2_t' => ['Contact Landlord', 'ቤት አከራዩን ያግኙ', 'Abbaa Mana Quunnamuu'],
    'step2_d' => ['Get the landlord\'s contact details and reach out directly to schedule a visit.', 'የቤት አከራዩን የመገናኛ አድራሻ ያግኙ እና የጉብኝት ቀን ለማስያዝ በቀጥታ ያነጋግሩ።', 'Teessoo quunnamtii abbaa manaa argadhaatii deemsa kaa\'anuuf kallattiin quunnamaa.'],
    'step3_t' => ['Move In', 'ወደ ቤት ይግቡ', 'Gara Mana Seensuu'],
    'step3_d' => ['Finalize your agreement and move into your new home or business space.', 'ስምምነትዎን ይደመድሙ እና ወደ አዲሱ ቤትዎ ወይም የንግድ ቦታዎ ይግቡ።', 'Walta\'iinsa keessan xumuraatii mana yookaan bakka daldalaa haaraa keessan seenaa.'],
    'footer_tagline' => ['The first digital marketplace for property rentals in Adama City. Connecting landlords and tenants directly, saving you time and money.', 'በአዳማ ከተማ ለንብረት ኪራይ የመጀመሪያው ዲጂታል ገበያ። ቤት አከራዮችን እና ተከራዮችን በቀጥታ ያገናኛል፣ ጊዜዎን እና ገንዘብዎን ይቆጥብልዎታል።', 'Gabaa dijitaalii jalqabaa kireeffama qabeenyaa Magaalaa Adaamaa keessatti. Abbootii manaati fi kireessitoota kallattiin walitti qunnamsiisa; yeroo fi maallaqa keessan isin hambisa.'],
    'footer_quick' => ['Quick Links', 'ፈጣን አገናኞች', 'Ambasadee Daddafaa'],
    'footer_browse' => ['Browse Properties', 'ንብረቶችን ይፈልጉ', 'Qabeenya Barbaadhaa'],
    'footer_signin' => ['Sign In', 'ይግቡ', 'Seensaa'],
    'footer_categories' => ['Categories', 'ምድቦች', 'Gosaawwan'],
    'footer_apartments' => ['Apartments', 'አፓርትመንቶች', 'Apartmentii'],
    'footer_villas' => ['Villas', 'ቪላዎች', 'Villa'],
    'footer_shops' => ['Shops', 'ሱቆች', 'Suuqii'],
    'footer_contact' => ['Contact', 'አግኙን', 'Quunnamuu'],
    'footer_rights' => ['All rights reserved.', 'ሁሉም መብቶች የተጠበቁ ናቸው።', 'Mirga hunda eegamaadha.'],
    'footer_terms' => ['Terms of Service', 'የአገልግሎት ውሎች', 'Waltiiwwan Tajaajilaa'],
    'footer_privacy' => ['Privacy Policy', 'የግላዊነት ፖሊሲ', 'Imaammata Icciitii'],
    'footer_adama' => ['Adama, Oromia', 'አዳማ፣ ኦሮሚያ', 'Adaamaa, Oromiyaa'],
    'footer_ethio' => ['Ethiopia', 'ኢትዮጵያ', 'Itoophiyaa'],
    'footer_hours' => ['Open every day', 'በየቀኑ ክፍት', 'Guyyaa hunda banaa'],
    'footer_reply' => ['We reply within 24h', 'በ24 ሰዓት ውስጥ እንመልሳለን', 'Sa\'aa 24 keessatti deebina'],

    // Browse / index page
    'find_properties' => ['Find Properties in Adama', 'በአዳማ ውስጥ ንብረቶችን ያግኙ', 'Adaamaa keessatti Qabeenya Barbaadhu'],
    'browse_eyebrow' => ['Rental Listings', 'የኪራይ ማስታወቂያዎች', 'Baallannoo Kireessa'],
    'browse_sub' => ['Browse verified rental properties across Adama — from single homes to office spaces.', 'በአዳማ የተረጋገጡ የኪራይ ንብረቶችን ያስሱ — ከነጠላ ቤቶች እስከ ቢሮ ቦታዎች።', 'Qabeenya kireessaa mirkaneeffamee Adaamaa keessaa barbaadhaa — mana tokkoo hanga bakka offiisaa.'],
    'popular' => ['Popular:', 'ታዋቂ፡', 'Beekamaa:'],
    'lbl_category' => ['Category', 'ምድብ', 'Gosa'],
    'lbl_location' => ['Location', 'አካባቢ', 'Iddoo'],
    'lbl_price' => ['Max Price', 'ከፍተኛ ዋጋ', 'Gatii Olii'],
    'lbl_sort' => ['Sort by', 'አደራደር', 'Filaannoo'],
    'listings_count' => ['listings', 'ማስታወቂያዎች', 'baallannoo'],
    'all_categories' => ['All Categories', 'ሁሉም ምድቦች', 'Gosa Hunda'],
    'residential' => ['Residential', 'የመኖሪያ', 'Jireenya'],
    'single_home' => ['Single Home', 'የግል ቤት', 'Mana Dhuunfaa'],
    'apartment' => ['Apartment', 'አፓርትመንት', 'Apartmentii'],
    'villa' => ['Villa', 'ቪላ', 'Villa'],
    'commercial' => ['Commercial', 'የንግድ', 'Daldalaa'],
    'office' => ['Office', 'ቢሮ', 'Waajjira'],
    'shop' => ['Shop', 'ሱቅ', 'Suuqii'],
    'warehouse' => ['Warehouse', 'መጋዘን', 'Madhala'],
    'search_kebele' => ['Search by Kebele...', 'በቀበሌ ይፈልጉ...', 'Hambaleedhaan barbaadi...'],
    'max_price' => ['Max Price (ETB)', 'ከፍተኛ ዋጋ (ብር)', 'Gatiin Olii (Birrii)'],
    'newest_first' => ['Newest First', 'አዲስ መጀመሪያ', 'Haaraa Jalqabaa'],
    'price_low' => ['Price: Low to High', 'ዋጋ፡ ከዝቅተኛ ወደ ከፍተኛ', 'Gatii: Gadiitii gara Olivatti'],
    'price_high' => ['Price: High to Low', 'ዋጋ፡ ከከፍተኛ ወደ ዝቅተኛ', 'Gatii: Oliitii gara Gadiittii'],
    'search_btn' => ['Search', 'ፈልግ', 'Barbaadi'],
    'reset_btn' => ['Reset', 'ሰርዝ', 'Haqi'],
    'no_properties' => ['No properties found', 'ምንም ንብረት አልተገኘም', 'Qabeenyi hin argamne'],
    'try_adjust' => ['Try adjusting your search filters or check back later.', 'የፍለጋዎን ማጣሪያ ያስተካክሉ ወይም በኋላ ይመልሱ።', 'Filtarra qorannoo keessan jijjiirraa ykn yeroo booda deebi\'aa.'],
    'load_more' => ['Load More', 'ተጨማሪ ይጫኑ', 'Dabalata Baachaa'],
    'etb_month' => ['ETB/month', 'ብር/በወር', 'Birrii/gara ji\'aa'],
    'notifications' => ['Notifications', 'ማሳወቂያዎች', 'Beeksisaa'],
    'unread' => ['unread', 'ያልተነበቡ', 'kan hin dubbifamne'],
    'no_notifications' => ['No notifications yet', 'እስካሁን ማሳወቂያ የለም', 'Beeksisa hin jiru'],
    'mark_all_read' => ['Mark all as read', 'ሁሉንም እንደተነበቡ ምልክት ያድርጉ', 'Hunda akka dubbifametti mallatteessi'],
    'new_posts' => ['New Posts', 'አዲስ ልጥፎች', 'Baallannaa Haaraa'],
    'role_landlord' => ['Landlord', 'ቤት አከራይ', 'Abbaa Mana'],
    'role_admin' => ['Admin', 'አስተዳዳሪ', 'Abbaa Taateessaa'],

    // Profile page
    'profile_info_title' => ['Personal Information', 'የግል መረጃ', 'Odeeffannoo Dhuunfaa'],
    'profile_info_sub' => ['Update your name, contact details and preferred contact numbers.', 'ስምዎን፣ የመገናኛ ዝርዝሮችዎን እና የመረጡትን የስልክ ቁጥሮች ያዘምኑ።', 'Maqaa, seensa quunnamtiiti fi lakkoofsota bilbilaa filatamo keessan haaromsaa.'],
    'phone_primary' => ['Primary Phone Number', 'ዋና የስልክ ቁጥር', 'Lakkoofsa Bilbilaa Guddaa'],
    'phone_additional' => ['Additional Phone Number', 'ተጨማሪ የስልክ ቁጥር', 'Lakkoofsa Bilbilaa Dabalataa'],
    'profile_optional' => ['(optional)', '(አማራጭ)', '(barbaachisaa miti)'],
    'profile_phone_hint' => ['Tenants and the owner are only shown the primary number when you list a property.', 'ተከራዮች እና ባለቤቱ ንብረት ሲዘረዝሩ የሚያዩት ዋናውን ቁጥር ብቻ ነው።', 'Kireessitoonnii fi abbicha, yeroo qabeenya baallattan lakkoofsa guddiftuu qofa argaatu.'],
    'profile_password_title' => ['Change Password', 'የይለፍ ቃል ይቀይሩ', 'Jecha Iccitii Jijjiiri'],
    'profile_password_sub' => ['Leave the field empty to keep your current password.', 'የአሁኑን የይለፍ ቃል ለማቆየት ቦታውን ባዶ ያድርጉት።', 'Jecha iccitii ammaa eeguuf bakka duwwaa dhiisaa.'],
    'new_password' => ['New Password', 'አዲስ የይለፍ ቃል', 'Jecha Iccitii Haaraa'],
    'new_password_ph' => ['Enter a new password (min 6 characters)', 'አዲስ የይለፍ ቃል ያስገቡ (ቢያንስ 6 ቁምፊዎች)', 'Jecha iccitii haaraa seensaa (xiqqaattis warnaa 6)'],
    'save_changes' => ['Save Changes', 'ለውጦችን አስቀምጥ', 'Jijjiirrawwan Qusadhu'],
    'phone_placeholder' => ['e.g. 0911234567', 'ለምሳሌ 0911234567', 'fkn. 0911234567'],

    // Dashboard / manage houses
    'dash_welcome' => ['Welcome, ', 'እንኳን ደህና መጡ፣ ', 'Baga nagaan dhufte, '],
    'dash_welcome_sub' => ['Manage your property listings from your personal dashboard.', 'የንብረት ዝርዝሮችዎን ከግል ዳሽቦርድዎ ያስተዳድሩ።', 'Baallannoonni qabeenyaa keessan dashboordii dhuunfaa keessaniitiin bulchaa.'],
    'stat_total' => ['Total Listings', 'አጠቃላይ ዝርዝሮች', 'Waliigala Baallannoo'],
    'stat_available' => ['Available', 'ይገኛል', 'Jira'],
    'stat_rented' => ['Rented', 'ተከራይቷል', 'Kireeffame'],
    'stat_pending' => ['Pending', 'በመጠባበቅ ላይ', 'Eegamaa'],
    'req_title' => ['Rental Requests', 'የኪራይ ጥያቄዎች', 'Gaaffilee Kireessituu'],
    'req_badge_pending' => ['pending', 'በመጠባበቅ ላይ', 'eegamaa'],
    'flash_status_saved' => ['Listing status updated.', 'የዝርዝሩ ሁኔታ ተዘምኗል።', 'Halli baallannoo haaromfame.'],
    'flash_accepted' => ['Rental request accepted. The tenant has been notified and the property is marked as rented.', 'የኪራይ ጥያቄ ተቀብሏል። ተከራዩ ተገልጿል እና ንብረቱ እንደተከራየ ተመዝግቧል።', 'Gaaffiin kireessituu fudhatame. Kireessituun beeksifame; qabeenyi kireeffameetti mallatteeffame.'],
    'flash_rejected' => ['Rental request declined. The tenant has been notified.', 'የኪራይ ጥያቄ ውድቅ ተደርጓል። ተከራዩ ተገልጿል።', 'Gaaffiin kireessituu didame. Kireessituun beeksifame.'],
    'req_empty' => ['No rental requests yet. When someone wants to rent your property, their request will appear here.', 'ገና የኪራይ ጥያቄ የለም። አንድ ሰው ንብረትዎን ለመከራየት ሲፈልግ ጥያቄው እዚህ ይታያል።', 'Gaaffiin kireessituu hin jiru. Yeroo namni qabeenya keessan kireeffachuu barbaadutti gaaffii isaanii asitti mul\'ata.'],
    'req_in_kebele' => ['in Kebele', 'በቀበሌ', 'hambalee keessatti'],
    'req_pending' => ['pending', 'በመጠባበቅ ላይ', 'eegamaa'],
    'req_accepted' => ['accepted', 'ተቀብሏል', 'fudhatame'],
    'req_rejected' => ['rejected', 'ውድቅ ተደርጓል', 'didame'],
    'req_completed' => ['completed', 'ተጠናቋል', 'xumurame'],
    'btn_accept' => ['Accept', 'ተቀበል', 'Fudhadhu'],
    'btn_reject' => ['Decline', 'ውድቅ', 'Didi'],
    'your_listings' => ['Your Listings', 'የእርስዎ ዝርዝሮች', 'Baallannoonni Keessan'],
    'mark_rented' => ['Mark Rented', 'ተከራይቷል ምልክት', 'Kireeffameeti Mallatteessi'],
    'mark_available' => ['Mark Available', 'ይገኛል ምልክት', 'Jiraati Mallatteessi'],
    'awaiting_approval' => ['Awaiting Approval', 'ፀድቆ በመጠባበቅ ላይ', 'Mirkanneessa Eeggachaa'],
    'not_available' => ['Not Available', 'አይገኝም', 'Hin Jiru'],
    'btn_edit' => ['Edit', 'አርትዕ', 'Jijjiiri'],
    'btn_delete' => ['Delete', 'ሰርዝ', 'Haqu'],
    'empty_listings_h' => ['No listings yet', 'እስካሁን ዝርዝር የለም', 'Baallannoon Hin Jiru'],
    'empty_listings_p' => ['Start listing your properties and reach potential tenants.', 'ንብረቶችዎን መዘርዘር ይጀምሩ እና እምቅ ተከራዮችን ያግኙ።', 'Qabeenya keessan baallachuu jalqabaa; kireessitoota hayyoo ta\'an qunnamaa.'],
    'post_first_house' => ['Post Your First House', 'የመጀመሪያ ቤትዎን ይለጥፉ', 'Mana Jalqabaa Keessan Baalladhaa'],
    'in_kebele' => ['Kebele', 'ቀበሌ', 'Hambalee'],

    // Post house page
    'ph_title' => ['Post a Property', 'ንብረት ይለጥፉ', 'Qabeenya Baalladhu'],
    'ph_page_heading' => ['Post a New Property', 'አዲስ ንብረት ይለጥፉ', 'Qabeenya Haaraa Baalladhu'],
    'ph_page_sub' => ['Fill in the details below to list your property for potential tenants.', 'ለሚፈልጉ ተከራዮች ንብረትዎን ለመዘርዘር ከዚህ በታች ያሉትን ዝርዝሮች ይሙሉ።', 'Kireessitoota barbaaddaniif qabeenya keessan baallachuuf bakka gadii guutaa.'],
    'ph_location' => ['Location Details', 'የቦታ ዝርዝሮች', 'Ibsa Iddoo'],
    'ph_kebele' => ['Kebele', 'ቀበሌ', 'Hambalee'],
    'ph_kebele_ph' => ['e.g. 03 or 12', 'ለምሳሌ 03 ወይም 12', 'fkn. 03 ykn 12'],
    'ph_house_number' => ['House Number', 'የቤት ቁጥር', 'Lakkoofsa Manaa'],
    'ph_house_number_ph' => ['e.g. 45', 'ለምሳሌ 45', 'fkn. 45'],
    'ph_street' => ['Street Name', 'የመንገድ ስም', 'Maqaa Daandii'],
    'ph_street_ph' => ['e.g. Bole Road', 'ለምሳሌ ቦሌ መንገድ', 'fkn. Daandii Boolee'],
    'ph_map_link' => ['Map Link', 'የካርታ አገናኝ', 'Bu\'aa Kaartaa'],
    'ph_map_ph' => ['Paste a Google Maps link here (optional)', 'የGoogle ካርታ አገናኝ እዚህ ያስቀምጡ (አማራጭ)', 'Bu\'aa Google Maps asitti gulufaa (filannoo)'],
    'ph_map_auto' => ['Auto-generate map link from location fields', 'ከቦታ መስኮች ላይ የካርታ አገናኝን በራስ-ሰር ያመንጩ', 'Bu\'aa kaartaa meeshaa iddoo irraa ofumaan uumi'],
    'ph_map_auto_note' => ['Auto-generated from location fields', 'ከቦታ መስኮች በራስ-ሰር የተፈጠረ', 'Meeshaa iddoo irraa ofumaan uumame'],
    'ph_property_info' => ['Property Information', 'የንብረት መረጃ', 'Odeeffannoo Qabeenyaa'],
    'ph_category' => ['Category', 'ምድብ', 'Gosa'],
    'ph_select_type' => ['Select type...', 'ዓይነት ይምረጡ...', 'Gosa filadhu...'],
    'ph_monthly_price' => ['Monthly Price (ETB)', 'የወርሃዊ ዋጋ (ብር)', 'Gatti Ji\'aa (Birrii)'],
    'ph_price_ph' => ['e.g. 8000', 'ለምሳሌ 8000', 'fkn. 8000'],
    'ph_contact_phone' => ['Contact Phone', 'የመገናኛ ስልክ', 'Bilbila Quunnamtii'],
    'ph_contact_phone_ph' => ['e.g. 0911234567', 'ለምሳሌ 0911234567', 'fkn. 0911234567'],
    'ph_description' => ['Description', 'መግለጫ', 'Ibsa'],
    'ph_desc_ph' => ['Describe your property - water, electricity, furnished status, etc.', 'ንብረትዎን ይግለጹ - ውሃ፣ ኤሌክትሪክ፣ የቤት እቃ ሁኔታ ወዘተ።', 'Qabeenya keessan ibsaa - bishaan, karaanta, haala meeshaa, fi kkf.'],
    'ph_photos' => ['Photos', 'ፎቶዎች', 'Suuraa'],
    'ph_property_photos' => ['Property Photos', 'የንብረት ፎቶዎች', 'Suuraa Qabeenyaa'],
    'ph_upload_click' => ['Click to upload photos', 'ፎቶዎችን ለመስቀል ጠቅ ያድርጉ', 'Suuraa olkeesuuf cuqaasi'],
    'ph_upload_hint' => ['JPG, PNG, WebP or GIF · the first photo becomes the cover · up to 6 photos (5MB each)', 'JPG፣ PNG፣ WebP ወይም GIF · የመጀመሪያው ፎቶ የሽፋን ይሆናል · እስከ 6 ፎቶዎች (እያንዳንዱ 5MB)', 'JPG, PNG, WebP ykn GIF · suuraan jalqabaa haguugaa ta\'a · suuraa hanga 6 (tokkoon tokko 5MB)'],
    'ph_amenities' => ['Amenities', 'መገልገያዎች', 'Meessaa'],
    'ph_amenities_hint' => ['Select all amenities that apply to your property', 'በንብረትዎ ላይ ያሉትን መገልገያዎች ሁሉ ይምረጡ', 'Meessaa qabeenya keessan keessatti argamu hunda filadhaa'],
    'ph_submit' => ['Submit for Approval', 'ለማጽደቅ ያስገቡ', 'Mirkanneessaaf Ergi'],
    'ph_submitted_title' => ['Submitted for Approval', 'ለማጽደቅ ቀርቧል', 'Mirkanneessaaf Ergame'],
    'ph_submitted_desc' => ['Your property is now in review. Once an admin approves it, it will go live on the marketplace.', 'ንብረትዎ አሁን በግምገማ ላይ ነው። አስተዳዳሪ ካጸደቀው በኋላ በገበያ ቦታ ላይ ይታያል።', 'Qabeenyi keessan amma sakatta\'amaa jira. Abbaan taateessaa yoomiruutti yoo mirkanneesse gabaa irratti mul\'ata.'],
    'ph_step_submitted' => ['Submitted', 'ቀርቧል', 'Ergame'],
    'ph_step_review' => ['In Review', 'በግምገማ ላይ', 'Sakatta\'aa'],
    'ph_step_live' => ['Live', 'በቀጥታ ስርጭት', 'Olkaa'],
    'ph_add_another' => ['Add Another', 'ሌላ ይጨምሩ', 'Kan Biroo Dabali'],
    'ph_go_dashboard' => ['Go to Dashboard', 'ወደ ዳሽቦርድ ይሂዱ', 'Gara Dashboordii Deemi'],
    'ph_upload_failed' => ['Upload failed', 'መስቀል አልተሳካም', 'Olkaasuun hin milkanoofne'],
    'ph_photo' => ['photo', 'ፎቶ', 'suuraa'],
    'ph_photos_plural' => ['photos', 'ፎቶዎች', 'suuraawwan'],
    'ph_selected' => ['selected: ', 'የተመረጡ፡ ', 'filaman: '],
    'ph_err_no_photo' => ['Please select at least one photo.', 'ቢያንስ አንድ ፎቶ ይምረጡ።', 'Xiqqaattis suuraa tokko filadhu.'],
    'ph_err_not_writable' => ['The uploads folder is not writable. Check permissions.', 'የ uploads አቃፊ መጻፊያ አይደለም። ፍቃዶቹን ይፈትሹ።', 'Forderiin uploads hin barreesifamne. Hayyama ilaali.'],
    'ph_err_ini_size' => ['File exceeds server upload limit.', 'ፋይሉ የሰርቨር የመጫን ገደብ አልፏል።', 'Faayiliin daangaa olkaasuu sirverii dabree jira.'],
    'ph_err_form_size' => ['File exceeds form upload limit.', 'ፋይሉ የቅጹን የመጫን ገደብ አልፏል።', 'Faayiliin daangaa olkaasuu foormii dabree jira.'],
    'ph_err_partial' => ['a file was only partially uploaded.', 'ፋይሉ በከፊል ብቻ ተጭኗል።', 'Faayiliin gartokkeen qofa olkaa\'e.'],
    'ph_err_no_tmp' => ['Missing temporary folder on server.', 'በሰርቨር ላይ ጊዜያዊ አቃፊ የለም።', 'Forderiin yeroodhaa sirverirraa dhabame.'],
    'ph_err_cant_write' => ['Failed to write file to disk.', 'ፋይሉን ወደ ዲስክ መጻፍ አልተሳካም።', 'Faayiliin gara diskitti barreesuun hin milkanoofne.'],
    'ph_err_extension' => ['Upload blocked by server extension.', 'መስቀል በሰርቨር ተጨማሪ ተዘግቷል።', 'Olkaasuun eksteenshini sirveriidhaan dhowwame.'],
    'ph_err_unknown' => ['Unknown upload error', 'ያልታወቀ የመጫን ስህተት', 'Dogoggora olkaasuu hin beekamne'],
    'ph_err_closing_paren' => [')', ')', ')'],
    'ph_err_bad_type' => ['Only JPG, PNG, WebP, GIF, HEIC or HEIF photos are allowed.', 'JPG፣ PNG፣ WebP፣ GIF፣ HEIC ወይም HEIF ፎቶዎች ብቻ ይፈቀዳሉ።', 'Suuraan JPG, PNG, WebP, GIF, HEIC ykn HEIF qofa hayyamama.'],
    'ph_err_heic' => ['Could not convert HEIC photo to JPEG.', 'የHEIC ፎቶን ወደ JPEG መቀየር አልተቻለም።', 'Suuraa HEIC gara JPEGtti jijjiiruu hin danda\'amne.'],
    'ph_err_move' => ['move_uploaded_file returned false. Check server error log.', 'move_uploaded_file ውሸት መለሰ። የሰርቨር ስህተት ምዝግብ ይፈትሹ።', 'move_uploaded_file soba deebise. Log dogoggoraa sirverii ilaali.'],
    'ph_err_no_valid' => ['No valid photos were processed.', 'ምንም ትክክለኛ ፎቶ አልተሰራም።', 'Suuraan sirrii olkaa\'u hin jiru.'],
    'ph_err_db' => ['Database error. Please try again.', 'የዳታቤዝ ስህተት። እባክዎ እንደገና ይሞክሩ።', 'Dogoggora daataa beensii. Kadhattee amma yaali.'],

    // Shared navigation / language
    'nav_browse' => ['Browse', 'ያስሱ', 'Barbaadi'],
    'lang_label' => ['Language', 'ቋንቋ', 'Afaan'],

    // House detail page
    'hd_notfound_title' => ['Listing Not Found', 'ማስታወቂያ አልተገኘም', 'Baallannoon hin argamne'],
    'hd_title_kebele' => ['Property in Kebele ', 'በቀበሌ ያለ ንብረት ', 'Qabeenya Hambalee '],
    'hd_photo_alt' => ['Property photo', 'የንብረት ፎቶ', 'Suuraa qabeenyaa'],
    'hd_prev_photo' => ['Previous photo', 'ቀዳሚ ፎቶ', 'Suuraa dursaa'],
    'hd_next_photo' => ['Next photo', 'ቀጣይ ፎቶ', 'Suuraa itti aanu'],
    'time_just_now' => ['Just now', 'አሁን', 'Amma'],
    'time_min_ago' => ['%d min ago', 'ከ%d ደቂቃ በፊት', 'Daqiiqaa %d dura'],
    'time_hr_ago' => ['%d hr ago', 'ከ%d ሰዓት በፊት', 'Sa\'aatii %d dura'],
    'time_d_ago' => ['%d d ago', 'ከ%d ቀን በፊት', 'Guyyaa %d dura'],
    'hd_notfound_h' => ['Listing unavailable', 'ማስታወቂያ አይገኝም', 'Baallannoon hin jiru'],
    'hd_notfound_p' => ['This property is either pending approval, rented, or no longer listed. Browse other available properties in Adama.', 'ይህ ንብረት በመጠባበቅ፣ ተከራይቷል፣ ወይም ከአሁን በኋላ አልተዘረዘረም። በአዳማ ውስጥ ሌሎች የሚገኙ ንብረቶችን ይፈልጉ።', 'Qabeenyi kun mirkanneessa eeggachaa, kireeffamee, ykn deebisanii hin baallamne. Qabeenya Adaamaa keessatti argaman biroo barbaadhaa.'],
    'hd_back_listings' => ['Back to Listings', 'ወደ ዝርዝሮች ተመለስ', 'Gara baallannoo deebi\'i'],
    'hd_about' => ['About this property', 'ስለዚህ ንብረት', 'Waa\'ee qabeenya kanaa'],
    'hd_quick_facts' => ['Quick facts', 'ፈጣን እውነታዎች', 'Odeeffannoo daddafaa'],
    'hd_street' => ['Street', 'መንገድ', 'Daandii'],
    'hd_house_no' => ['House No.', 'ቤት ቁጥር', 'Lakkoofsa Manaa'],
    'hd_request_rent' => ['Request to Rent', 'ለመከራየት ጥያቄ ይጠይቁ', 'Kireeffachuuf gaafadhu'],
    'hd_cancel_request' => ['Cancel Request', 'ጥያቄ ሰርዝ', 'Gaaffii Haqi'],
    'hd_call_owner' => ['Call Owner', 'ባለቤቱን ይደውሉ', 'Abbicha Bilbili'],
    'hd_rented_note' => ['This property is currently rented and cannot be reserved.', 'ይህ ንብረት በአሁኑ ጊዜ ተከራይቷል እና ማስያዝ አይቻልም።', 'Qabeenyi kun amma kireeffamee jira; kaa\'achuun hin danda\'amu.'],
    'hd_view_map' => ['View on Map', 'በካርታ ይመልከቱ', 'Kaartaan ilaali'],
    'hd_property_owner' => ['Property Owner', 'የንብረት ባለቤት', 'Abbaa Qabeenyaa'],
    'hd_listed' => ['Listed ', 'የተዘረዘረ ', 'Baallame '],
    'hd_signin_required' => ['Sign in required', 'መግባት ያስፈልጋል', 'Seensuu barbaachisa'],
    'hd_signin_msg' => ['You need to sign in before requesting this property.', 'ይህን ንብረት ለመጠየቅ በመጀመሪያ መግባት ያስፈልግዎታል።', 'Qabeenya kana gaafachuuf jalqaba seenuu qabdu.'],
    'hd_go_login' => ['Go to Login', 'ወደ መግቢያ ይሂዱ', 'Gara seenuu deemi'],
    'hd_cancel_req_title' => ['Cancel rental request', 'የኪራይ ጥያቄ ይሰርዙ', 'Gaaffii kireeffamaa haqi'],
    'hd_cancel_req_msg' => ['Do you want to withdraw your request to rent this property?', 'ይህን ንብረት ለመከራየት ያቀረቡትን ጥያቄ መሰረዝ ይፈልጋሉ?', 'Gaaffii qabeenya kana kireeffachuuf baallatan haquu barbaadduu?'],
    'hd_req_msg' => ['Send a rental request to the owner of this property? The owner will be notified immediately.', 'ለዚህ ንብረት ባለቤት የኪራይ ጥያቄ ይላኩ? ባለቤቱ ወዲያውኑ ይነገራል።', 'Abbaa qabeenya kanaa gaaffii kireeffamaa erguu? Abbaan yommusuma beekkama.'],
    'hd_send_req' => ['Send Request', 'ጥያቄ ላክ', 'Gaaffii Ergi'],
    'hd_srv_error' => ['Could not reach the server. Please try again.', 'ሰርቨሩ መድረስ አልተቻለም። እባክዎ እንደገና ይሞክሩ።', 'Sirveriinu argachuu hin dandeenye. Kadhattee amma yaali.'],
    'hd_error' => ['Error', 'ስህተት', 'Dogoggora'],
    'hd_calling' => ['Calling the property owner...', 'የንብረት ባለቤትን እየደወልን ነው...', 'Abbaa qabeenyaa bilbillaa...'],
    'hd_calling_owner' => ['Calling owner', 'ባለቤቱን እየደወልን', 'Abbichaa bilbillaa'],
    'hd_wait_approved' => ['Wait until your request is approved.', 'ጥያቄዎ እስኪጸድቅ ይጠብቁ።', 'Hanga gaaffiin keessan mirkaneeffamutti eegaa.'],
    'hd_req_pending' => ['Request pending', 'ጥያቄ በመጠባበቅ ላይ', 'Gaaffiin eegamaa'],
    'hd_ask_first' => ['First ask a request.', 'በመጀመሪያ ጥያቄ ይጠይቁ።', 'Jalqaba gaaffii gaafadhaa.'],
    'hd_req_required' => ['Request required', 'ጥያቄ ያስፈልጋል', 'Gaaffiin barbaachisa'],

    // Edit house page
    'eh_title' => ['Edit Property', 'ንብረት አርትዕ', 'Qabeenya Jijjiiri'],
    'eh_page_sub' => ['Update your listing details below.', 'የዝርዝሩን ዝርዝሮች ከዚህ በታች ያዘምኑ።', 'Ibsa baallannoo keessan gadii haaromsaa.'],
    'eh_preview' => ['Preview', 'ቅድመ እይታ', 'Durtii'],
    'eh_cover' => ['Cover', 'ሽፋን', 'Haguugaa'],
    'eh_cover_photo' => ['Cover photo', 'የሽፋን ፎቶ', 'Suuraa Haguugaa'],
    'eh_change_cover' => ['Change Cover Photo', 'የሽፋን ፎቶ ይቀይሩ', 'Suuraa Haguugaa Jijjiiri'],
    'eh_cover_hint' => ['JPG, PNG, WebP, GIF, HEIC or HEIF · 5MB max · HEIC is converted automatically', 'JPG፣ PNG፣ WebP፣ GIF፣ HEIC ወይም HEIF · ቢበዛ 5MB · HEIC በራስ-ሰር ይቀየራል', 'JPG, PNG, WebP, GIF, HEIC ykn HEIF · olaantummaa 5MB · HEIC ofumaan jijjiirama'],
    'eh_gallery' => ['Gallery Photos', 'የፎቶ ማእከለ-ስዕላት', 'Suuraa Gooree'],
    'eh_remove' => ['Remove', 'አስወግድ', 'Haqi'],
    'eh_remove_hint' => ['Click the × on a photo to mark it for removal before saving.', 'ከማስቀመጥዎ በፊት ፎቶ ለመሰረዝ በ× ላይ ጠቅ ያድርጉ።', 'Qusachuu dura suuraa haquuf × irratti cuqaasi.'],
    'eh_add_more' => ['Add More Photos', 'ተጨማሪ ፎቶዎች ይጨምሩ', 'Suuraa Dabalataa Dabali'],
    'eh_add_photos' => ['Add Photo(s)', 'ፎቶ(ዎች) ይጨምሩ', 'Suuraa Dabali'],
    'eh_add_hint' => ['Up to 6 photos total · JPG, PNG, WebP, GIF, HEIC or HEIF', 'በአጠቃላይ እስከ 6 ፎቶዎች · JPG፣ PNG፣ WebP፣ GIF፣ HEIC ወይም HEIF', 'Waliigala suuraa 6 · JPG, PNG, WebP, GIF, HEIC ykn HEIF'],
    'eh_save' => ['Save Changes', 'ለውጦችን አስቀምጥ', 'Jijjiirrawwan Qusadhu'],
    'eh_cancel' => ['Cancel', 'ሰርዝ', 'Haqi'],
    'eh_saved_title' => ['Changes Saved', 'ለውጦች ተቀምጠዋል', 'Jijjiirrawwan Qusataman'],
    'eh_saved_desc' => ['Your changes have been submitted and will be reviewed by an admin. The listing will go live again once approved.', 'ለውጦችዎ ቀርበዋል እና በአስተዳዳሪ ይገመገማሉ። ከጸደቀ በኋላ ማስታወቂያው እንደገና ይታያል።', 'Jijjiirrawwan keessan ergamaniiru; abbaan taateessaa sakatta\'a. Yoo mirkanneesse baallannoon amma illee olkaa\'a.'],
    'eh_keep_editing' => ['Keep Editing', 'ማርትዕ ቀጥል', 'Jijjiiruu Fufi'],
    'eh_new_post' => ['New Post', 'አዲስ ልጥፍ', 'Baallannaa Haaraa'],
    'eh_err_fill' => ['Please fill in Kebele, Street, Category, Price and Phone.', 'እባክዎ ቀበሌ፣ መንገድ፣ ምድብ፣ ዋጋ እና ስልክ ይሙሉ።', 'Kadhattee Hambalee, Daandii, Gosa, Gatii fi Bilbila guuti.'],
    'eh_db_error' => ['Database error. Your changes were not saved.', 'የዳታቤዝ ስህተት። ለውጦችዎ አልተቀመጡም።', 'Dogoggora daataa beensii. Jijjiirrawwan keessan hin qusatamne.'],
    'ph_err_save_file' => ['Could not save the uploaded file. Check server error log.', 'የተሰቀለውን ፋይል ማስቀመጥ አልተቻለም። የሰርቨር ስህተት ምዝግብ ይፈትሹ።', 'Faayilii olkaa\'ame qusachuu hindanda\'amne. Log dogoggoraa sirverii ilaali.'],
    'eh_undo_remove' => ['Undo remove', 'መሰረዝ ቀልስ', 'Haquu Deebisi'],
    'eh_remove_photo' => ['Remove photo', 'ፎቶ አስወግድ', 'Suuraa Haqi'],
];

// Map known English messages -> [en, am, om]
$ERR_MAP = [
    'Invalid email or password.' => ['Invalid email or password.', 'የተሳሳተ ኢሜይል ወይም የይለፍ ቃል።', 'Imaayelii ykn jecha iccitii dogoggoraa.'],
    'Invalid password. Please try again.' => ['Invalid password. Please try again.', 'የይለፍ ቃሉ የተሳሳተ ነው። እባክዎ እንደገና ይሞክሩ።', 'Jecha iccitii dogoggoraa. Kadhattee amma yaali.'],
    'No account found with this email address.' => ['No account found with this email address.', 'በዚህ ኢሜይል አድራሻ መለያ አልተገኘም።', 'Akkaawuntii teessoo imaayelii kana wajjin hin argamne.'],
    'Sign-in with Google was cancelled.' => ['Sign-in with Google was cancelled.', 'በGoogle መግባት ተሰርዟል።', 'Seensi Google dhoofame.'],
    'Google sign-in failed. Please try again, or use email &amp; password.' => ['Google sign-in failed. Please try again, or use email &amp; password.', 'የGoogle መግቢያ አልተሳካም። እባክዎ እንደገና ይሞክሩ፣ ወይም በኢሜይል እና በይለፍ ቃል ይግቡ።', 'Seensi Google hin milkanoofne. Amma yaali, ykn imaayelii fi jecha iccitii fayyadamaa.'],
    'This email is already verified — sign in below.' => ['This email is already verified — sign in below.', 'ይህ ኢሜይል አስቀድሞ ተረጋግጧል — ከዚህ በታች ይግቡ።', 'Imaayeliin kun duraan mirkaneeffame — gadii seena.'],
    'Password must be at least 6 characters.' => ['Password must be at least 6 characters.', 'የይለፍ ቃል ቢያንስ 6 ቁምፊዎች መሆን አለበት።', 'Jecha iccitii, xiqqaattis warnaa 6 ta\'uu qaba.'],
    'That email address is not valid or its domain can\'t receive mail. Please double-check it and try again.' => ['That email address is not valid or its domain can\'t receive mail. Please double-check it and try again.', 'ያ ኢሜይል አድራሻ የተሳሳተ ነው ወይም ጎራው ደብዳቤ መቀበል አይችልም። እባክዎ አጣርተው እንደገና ይሞክሩ።', 'Teessoon imaayelii kun sirrii miti ykn domaaniin xalayaa fudhachuu hin danda\'u. Kadhattee irra deddeebi\'aa ilaaliitii amma yaali.'],
    'An account with this email already exists.' => ['An account with this email already exists.', 'በዚህ ኢሜይል መለያ ቀድሞ አለ።', 'Akkaawuntii imaayelii kana wajjin duraan jira.'],
    'An admin account already exists. The setup key is no longer valid.' => ['An admin account already exists. The setup key is no longer valid.', 'የአስተዳዳሪ መለያ ቀድሞ አለ። የማዋቀሪያ ቁልፉ ከአሁን በኋላ የሚሰራ አይደለም።', 'Akkaawuntii abbaa taateessaa duraan jira. Furtuun sirnaa kanaan booda hin hojjatu.'],
    'Invalid admin setup key. Please check and try again.' => ['Invalid admin setup key. Please check and try again.', 'የተሳሳተ የአስተዳዳሪ የማዋቀሪያ ቁልፍ። እባክዎ አጣርተው እንደገና ይሞክሩ።', 'Furtuu sirnaa abbaa taateessaa dogoggoraa. Kadhattee ilaaliitii amma yaali.'],
    'Registration failed. Please try again.' => ['Registration failed. Please try again.', 'ምዝገባ አልተሳካም። እባክዎ እንደገና ይሞክሩ።', 'Galmeessuun hin milkanoofne. Kadhattee amma yaali.'],
    'Your password has been reset. Please sign in.' => ['Your password has been reset. Please sign in.', 'የይለፍ ቃልዎ ተቀይሯል። እባክዎ ይግቡ።', 'Jecha iccitii keessan haaromfameera. Kadhattee seenaa.'],
];

function lang_index() {
    global $lang;
    if($lang === 'am') return 1;
    if($lang === 'om') return 2;
    return 0;
}

function t($key) {
    global $T;
    if(!isset($T[$key])) return $key;
    $i = lang_index();
    return $T[$key][$i];
}

function tout($text) {
    global $ERR_MAP;
    if(isset($ERR_MAP[$text])) return $ERR_MAP[$text][lang_index()];
    return $text;
}

function t_status($status) {
    switch($status){
        case 'Available': return t('stat_available');
        case 'Rented':    return t('stat_rented');
        case 'Pending':   return t('stat_pending');
        case 'Not Available': return t('not_available');
        default: return $status;
    }
}

function lang_switch_url($target) {
    $p = $_GET;
    $p['lang'] = $target;
    $qs = http_build_query($p);
    return basename($_SERVER['SCRIPT_NAME']) . ($qs ? '?' . $qs : '');
}