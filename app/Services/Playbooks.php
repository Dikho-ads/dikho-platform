<?php

namespace App\Services;

/**
 * Category playbooks: what each kind of shop sells and says, written natively per language.
 * "en" is the Hinglish most owners and customers type on WhatsApp; "hi" and "mr" are Devanagari.
 * Placeholders: {shop} {owner} {area} {city} {years} {occasion}.
 */
final class Playbooks
{
    public const LANGUAGES = ['en' => 'Hinglish', 'hi' => 'हिन्दी', 'mr' => 'मराठी'];

    /**
     * @var array<string, array<string, array<string, string>|list<string>>>
     */
    public const CATEGORIES = [
        'mobile' => [
            'offer' => ['en' => 'Purana phone laao, ₹2,000 extra pao', 'hi' => 'पुराना फ़ोन लाओ, ₹2,000 एक्स्ट्रा पाओ', 'mr' => 'जुना फोन आणा, ₹2,000 जास्त मिळवा'],
            'terms' => ['en' => 'Exchange value as per phone condition', 'hi' => 'एक्सचेंज कीमत फ़ोन की हालत के अनुसार', 'mr' => 'एक्सचेंज किंमत फोनच्या स्थितीनुसार'],
            'local' => ['en' => 'purana phone ghar pe pada hai?', 'hi' => 'पुराना फ़ोन घर पे पड़ा है?', 'mr' => 'जुना फोन घरी पडलाय?'],
            'newness' => ['en' => 'Naye 5G phone aa gaye, ₹9,999 se', 'hi' => 'नए 5G फ़ोन आ गए, ₹9,999 से', 'mr' => 'नवे 5G फोन आले, ₹9,999 पासून'],
            'tip' => ['en' => 'Phone lene se pehle 5 cheezein check karein', 'hi' => 'फ़ोन लेने से पहले 5 चीज़ें देखें', 'mr' => 'फोन घेण्याआधी या 5 गोष्टी तपासा'],
            'cta' => ['en' => 'WhatsApp karo, price batayenge', 'hi' => 'WhatsApp करें, दाम बताएंगे', 'mr' => 'WhatsApp करा, किंमत सांगतो'],
            'products' => ['5G phone', 'Screen repair', 'Earbuds', 'Fast charger', 'Phone cover'],
        ],
        'kirana' => [
            'offer' => ['en' => 'Mahine ka ration kit ₹2,499, ghar tak', 'hi' => 'महीने का राशन किट ₹2,499, घर तक', 'mr' => 'महिन्याचा किराणा किट ₹2,499, घरपोच'],
            'terms' => ['en' => 'Free delivery above ₹500 within 3 km', 'hi' => '₹500 से ऊपर 3 km तक फ्री डिलीवरी', 'mr' => '₹500 वरील ऑर्डरवर 3 km पर्यंत मोफत डिलिव्हरी'],
            'local' => ['en' => 'list WhatsApp karo, saman ghar aayega', 'hi' => 'लिस्ट WhatsApp करो, सामान घर आएगा', 'mr' => 'यादी WhatsApp करा, सामान घरी येईल'],
            'newness' => ['en' => 'Naya stock: dry fruits aur winter special', 'hi' => 'नया स्टॉक: ड्राई फ्रूट्स और सर्दी स्पेशल', 'mr' => 'नवा माल: सुकामेवा आणि हिवाळा स्पेशल'],
            'tip' => ['en' => 'Asli ghee pehchanne ke 3 tareeke', 'hi' => 'असली घी पहचानने के 3 तरीके', 'mr' => 'खरं तूप ओळखण्याच्या 3 पद्धती'],
            'cta' => ['en' => 'List bhejo WhatsApp pe', 'hi' => 'लिस्ट WhatsApp पर भेजें', 'mr' => 'यादी WhatsApp वर पाठवा'],
            'products' => ['Monthly ration kit', 'Cold-pressed oil', 'Dry fruits', 'Basmati rice', 'Puja kit'],
        ],
        'sweets' => [
            'offer' => ['en' => '1 kg mithai box par 250 g free', 'hi' => '1 किलो मिठाई डिब्बे पर 250 ग्राम फ्री', 'mr' => '1 किलो मिठाई बॉक्सवर 250 ग्रॅम मोफत'],
            'terms' => ['en' => 'On boxes of ₹600 and above, till stock lasts', 'hi' => '₹600 और ऊपर के डिब्बों पर, स्टॉक रहने तक', 'mr' => '₹600 व त्यावरील बॉक्सवर, माल असेपर्यंत'],
            'local' => ['en' => 'aaj shaam ki chai ke saath kya?', 'hi' => 'आज शाम की चाय के साथ क्या?', 'mr' => 'आज संध्याकाळच्या चहासोबत काय?'],
            'newness' => ['en' => 'Taaze ukadiche modak, roz subah', 'hi' => 'ताज़े उकडीचे मोदक, रोज़ सुबह', 'mr' => 'ताजे उकडीचे मोदक, रोज सकाळी'],
            'tip' => ['en' => 'Shuddh khoya ki pehchaan, 3 aasaan tareeke', 'hi' => 'शुद्ध खोये की पहचान, 3 आसान तरीके', 'mr' => 'शुद्ध खव्याची ओळख, 3 सोप्या पद्धती'],
            'cta' => ['en' => 'Order WhatsApp pe, pickup 30 min mein', 'hi' => 'WhatsApp पर ऑर्डर, 30 मिनट में तैयार', 'mr' => 'WhatsApp वर ऑर्डर, 30 मिनिटांत तयार'],
            'products' => ['Kaju katli', 'Ukadiche modak', 'Puran poli', 'Bakarwadi', 'Gift box'],
        ],
        'salon' => [
            'offer' => ['en' => 'Haircut + head massage sirf ₹299', 'hi' => 'हेयरकट + हेड मसाज सिर्फ़ ₹299', 'mr' => 'हेअरकट + हेड मसाज फक्त ₹299'],
            'terms' => ['en' => 'Weekdays 11 am to 5 pm, by appointment', 'hi' => 'सोम-शुक्र सुबह 11 से शाम 5, अपॉइंटमेंट से', 'mr' => 'सोम-शुक्र सकाळी 11 ते संध्या 5, अपॉइंटमेंटने'],
            'local' => ['en' => 'shaadi mein jaana hai? pehle yahan aao', 'hi' => 'शादी में जाना है? पहले यहाँ आओ', 'mr' => 'लग्नाला जायचंय? आधी इकडे या'],
            'newness' => ['en' => 'Naya bridal package, trial free', 'hi' => 'नया ब्राइडल पैकेज, ट्रायल फ्री', 'mr' => 'नवं ब्रायडल पॅकेज, ट्रायल मोफत'],
            'tip' => ['en' => 'Sardi mein baal rukhe? 3 aasaan upaay', 'hi' => 'सर्दी में बाल रूखे? 3 आसान उपाय', 'mr' => 'थंडीत केस कोरडे? 3 सोपे उपाय'],
            'cta' => ['en' => 'Slot book karo WhatsApp pe', 'hi' => 'WhatsApp पर स्लॉट बुक करें', 'mr' => 'WhatsApp वर वेळ बुक करा'],
            'products' => ['Haircut', 'Bridal makeup', 'Facial', 'Hair spa', 'Beard styling'],
        ],
        'clinic' => [
            'offer' => ['en' => 'Full body checkup ₹999, report same day', 'hi' => 'फुल बॉडी चेकअप ₹999, रिपोर्ट उसी दिन', 'mr' => 'फुल बॉडी चेकअप ₹999, रिपोर्ट त्याच दिवशी'],
            'terms' => ['en' => 'Fasting sample 7 to 10 am', 'hi' => 'खाली पेट सैंपल सुबह 7 से 10', 'mr' => 'उपाशीपोटी सॅम्पल सकाळी 7 ते 10'],
            'local' => ['en' => 'ghar ke bado ka checkup kab hua tha?', 'hi' => 'घर के बड़ों का चेकअप कब हुआ था?', 'mr' => 'घरातल्या मोठ्यांची तपासणी कधी केली?'],
            'newness' => ['en' => 'Ab Sunday ko bhi OPD, 9 se 1', 'hi' => 'अब रविवार को भी OPD, 9 से 1', 'mr' => 'आता रविवारीही OPD, 9 ते 1'],
            'tip' => ['en' => 'Sugar check karne ka sahi samay', 'hi' => 'शुगर जाँचने का सही समय', 'mr' => 'शुगर तपासण्याची योग्य वेळ'],
            'cta' => ['en' => 'Appointment WhatsApp pe', 'hi' => 'WhatsApp पर अपॉइंटमेंट', 'mr' => 'WhatsApp वर अपॉइंटमेंट'],
            'products' => ['Full body checkup', 'Diabetes care', 'Child vaccination', 'Physiotherapy', 'Medicines home delivery'],
        ],
        'coaching' => [
            'offer' => ['en' => 'Pehli 2 class free, phir decide karo', 'hi' => 'पहली 2 क्लास फ्री, फिर तय करो', 'mr' => 'पहिले 2 क्लास मोफत, मग ठरवा'],
            'terms' => ['en' => 'New batch from the 1st, 20 seats', 'hi' => '1 तारीख से नया बैच, 20 सीटें', 'mr' => '1 तारखेपासून नवी बॅच, 20 जागा'],
            'local' => ['en' => 'board exam ka darr? saath padhenge', 'hi' => 'बोर्ड परीक्षा का डर? साथ पढ़ेंगे', 'mr' => 'बोर्ड परीक्षेची भीती? सोबत अभ्यास करू'],
            'newness' => ['en' => 'Naya weekend batch, 10th aur 12th', 'hi' => 'नया वीकेंड बैच, 10वीं और 12वीं', 'mr' => 'नवी वीकेंड बॅच, 10वी आणि 12वी'],
            'tip' => ['en' => 'Board exam se pehle 30 din ka plan', 'hi' => 'बोर्ड परीक्षा से पहले 30 दिन का प्लान', 'mr' => 'बोर्ड परीक्षेआधी 30 दिवसांचा प्लॅन'],
            'cta' => ['en' => 'Demo class book karo', 'hi' => 'डेमो क्लास बुक करें', 'mr' => 'डेमो क्लास बुक करा'],
            'products' => ['10th board batch', '12th science', 'Spoken English', 'Weekend batch', 'Test series'],
        ],
    ];

    /**
     * Hook templates per angle; two variants each.
     *
     * @var array<string, array<string, list<string>>>
     */
    public const ANGLES = [
        'offer' => [
            'en' => ['{offer}', '{area} special: {offer}'],
            'hi' => ['{offer}', '{area} स्पेशल: {offer}'],
            'mr' => ['{offer}', '{area} स्पेशल: {offer}'],
        ],
        'trust' => [
            'en' => ['{years} saal, hazaaron customers. {owner} ji ka bharosa.', '{city} ka bharosa, {years} saal se'],
            'hi' => ['{years} साल, हज़ारों ग्राहक। {owner} जी का भरोसा।', '{years} साल से {city} का भरोसा'],
            'mr' => ['{years} वर्षं, हजारो ग्राहक. {owner} यांचा विश्वास.', '{years} वर्षांपासून {city} मधला विश्वास'],
        ],
        'local' => [
            'en' => ['{area} waalon, {local}', '{area} mein sabse paas: {shop}'],
            'hi' => ['{area} वालों, {local}', '{area} में सबसे पास: {shop}'],
            'mr' => ['{area} भागातल्या मित्रांनो, {local}', '{area} मध्ये सगळ्यात जवळ: {shop}'],
        ],
        'newness' => [
            'en' => ['{newness}', 'Abhi aaya: {newness}'],
            'hi' => ['{newness}', 'अभी आया: {newness}'],
            'mr' => ['{newness}', 'आत्ताच आलं: {newness}'],
        ],
        'festival' => [
            'en' => ['{occasion} ki shubhkamnayein!', '{occasion} special at {shop}'],
            'hi' => ['{occasion} की हार्दिक शुभकामनाएं!', '{occasion} पर {shop} का तोहफ़ा'],
            'mr' => ['{occasion}च्या हार्दिक शुभेच्छा!', '{occasion} निमित्त {shop} कडून खास भेट'],
        ],
        'tip' => [
            'en' => ['{tip}', 'Kaam ki baat: {tip}'],
            'hi' => ['{tip}', 'काम की बात: {tip}'],
            'mr' => ['{tip}', 'कामाची गोष्ट: {tip}'],
        ],
    ];

    /**
     * Why an angle works, shown under each variant.
     *
     * @var array<string, string>
     */
    public const ANGLE_WHY = [
        'offer' => 'Leads with the real number and a true deadline. Best on sale days.',
        'trust' => 'Your name and years do the selling. Strong with people wary of fake offers.',
        'local' => 'Speaks to your street by name, so nearby people feel it is for them.',
        'newness' => 'New stock gives people a reason to come and look today.',
        'festival' => 'Greeting first, offer second. Right for festival days.',
        'tip' => 'Useful advice builds trust and gets shared; no hard sell.',
    ];

    /**
     * Formats a shop can make and what each costs in credits.
     *
     * @var array<string, array{label: string, credits: int, ratio: string}>
     */
    public const FORMATS = [
        'post' => ['label' => 'Post', 'credits' => 2, 'ratio' => '4:5'],
        'status' => ['label' => 'WhatsApp Status', 'credits' => 2, 'ratio' => '9:16'],
        'carousel' => ['label' => 'Carousel · 5 cards', 'credits' => 5, 'ratio' => '4:5'],
        'reel' => ['label' => '15 s reel', 'credits' => 15, 'ratio' => '9:16'],
        'ad' => ['label' => 'AI-scene ad', 'credits' => 6, 'ratio' => '4:5'],
        'whatsapp' => ['label' => 'WhatsApp offer', 'credits' => 3, 'ratio' => '1:1'],
        'google_post' => ['label' => 'Google post', 'credits' => 2, 'ratio' => '4:3'],
        'print' => ['label' => 'A5 flyer', 'credits' => 4, 'ratio' => 'A5'],
        'sms' => ['label' => 'SMS', 'credits' => 1, 'ratio' => '1:1'],
        'review_reply' => ['label' => 'Review reply', 'credits' => 0, 'ratio' => '1:1'],
    ];

    /**
     * @return array<string, array<string, string>|list<string>>
     */
    public static function category(string $category): array
    {
        return self::CATEGORIES[$category] ?? self::CATEGORIES['kirana'];
    }
}
