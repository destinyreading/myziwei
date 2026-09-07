<?php
// BaZi constants and lookup data

$TG     = ['甲','乙','丙','丁','戊','己','庚','辛','壬','癸'];
$DZ     = ['子','丑','寅','卯','辰','巳','午','未','申','酉','戌','亥'];
$SS     = ['比肩','劫财','食神','伤官','偏财','正财','七杀','正官','偏印','正印'];
$SS_PY  = ['Bǐ Jiān','Jié Cái','Shí Shén','Shāng Guān','Piān Cái','Zhèng Cái','Qī Shā','Zhèng Guān','Piān Yìn','Zhèng Yìn'];
$CS_NM  = ['长生','沐浴','冠带','临官','帝旺','衰','病','死','墓','绝','胎','养'];
$CS_PY  = ['Cháng Shēng','Mù Yù','Guān Dài','Lín Guān','Dì Wàng','Shuāi','Bìng','Sǐ','Mù','Jué','Tāi','Yǎng'];
$CS_EN  = ['Prosperous Growth','Bath','Coming of Age','Officer','Prosperous Peak','Decline','Sickness','Death','Tomb','Void','Embryo','Nourish'];
$CS_ST  = [11,6,2,9,2,9,5,0,8,3];
$TG_EL  = [0,0,1,1,2,2,3,3,4,4];
$DZ_EL  = [4,2,0,0,2,1,1,2,3,3,2,4];
$EL_ZH  = ['木','火','土','金','水'];
$EL_NM  = ['Wood','Fire','Earth','Metal','Water'];
$SHENG  = [1,2,3,4,0];
$KE     = [2,3,4,0,1];

$NY = [
'海中金','海中金','炉中火','炉中火','大林木','大林木','路旁土','路旁土','剑锋金','剑锋金',
'山头火','山头火','涧下水','涧下水','城墙土','城墙土','白蜡金','白蜡金','杨柳木','杨柳木',
'泉中水','泉中水','屋上土','屋上土','霹雳火','霹雳火','松柏木','松柏木','长流水','长流水',
'沙中金','沙中金','山下火','山下火','平地木','平地木','壁上土','壁上土','金箔金','金箔金',
'覆灯火','覆灯火','天河水','天河水','大驿土','大驿土','钗钏金','钗钏金','桑柘木','桑柘木',
'大溪水','大溪水','沙中土','沙中土','天上火','天上火','石榴木','石榴木','大海水','大海水'
];
$NY_PY = [
'Hǎi Zhōng Jīn','Hǎi Zhōng Jīn','Lú Zhōng Huǒ','Lú Zhōng Huǒ','Dà Lín Mù','Dà Lín Mù',
'Lù Páng Tǔ','Lù Páng Tǔ','Jiàn Fēng Jīn','Jiàn Fēng Jīn','Shān Tóu Huǒ','Shān Tóu Huǒ',
'Jiàn Xià Shuǐ','Jiàn Xià Shuǐ','Chéng Qiáng Tǔ','Chéng Qiáng Tǔ','Bái Là Jīn','Bái Là Jīn',
'Yáng Liǔ Mù','Yáng Liǔ Mù','Quán Zhōng Shuǐ','Quán Zhōng Shuǐ','Wū Shàng Tǔ','Wū Shàng Tǔ',
'Pī Lì Huǒ','Pī Lì Huǒ','Sōng Bǎi Mù','Sōng Bǎi Mù','Cháng Liú Shuǐ','Cháng Liú Shuǐ',
'Shā Zhōng Jīn','Shā Zhōng Jīn','Shān Xià Huǒ','Shān Xià Huǒ','Píng Dì Mù','Píng Dì Mù',
'Bì Shàng Tǔ','Bì Shàng Tǔ','Jīn Bó Jīn','Jīn Bó Jīn','Fù Dēng Huǒ','Fù Dēng Huǒ',
'Tiān Hé Shuǐ','Tiān Hé Shuǐ','Dà Yì Tǔ','Dà Yì Tǔ','Chāi Chuàn Jīn','Chāi Chuàn Jīn',
'Sāng Zhè Mù','Sāng Zhè Mù','Dà Xī Shuǐ','Dà Xī Shuǐ','Shā Zhōng Tǔ','Shā Zhōng Tǔ',
'Tiān Shàng Huǒ','Tiān Shàng Huǒ','Shí Liú Mù','Shí Liú Mù','Dà Hǎi Shuǐ','Dà Hǎi Shuǐ'
];
$NY_ID = [
'Emas dalam laut','Emas dalam laut','Api dalam tungku','Api dalam tungku',
'Kayu hutan besar','Kayu hutan besar','Tanah tepi jalan','Tanah tepi jalan',
'Emas ujung pedang','Emas ujung pedang','Api puncak gunung','Api puncak gunung',
'Air bawah jurang','Air bawah jurang','Tanah tembok kota','Tanah tembok kota',
'Emas lilin putih','Emas lilin putih','Kayu pohon willow','Kayu pohon willow',
'Air mata air','Air mata air','Tanah atas atap','Tanah atas atap',
'Api petir','Api petir','Kayu pinus cemara','Kayu pinus cemara',
'Air mengalir panjang','Air mengalir panjang','Emas dalam pasir','Emas dalam pasir',
'Api bawah gunung','Api bawah gunung','Kayu tanah datar','Kayu tanah datar',
'Tanah di dinding','Tanah di dinding','Emas lembaran tipis','Emas lembaran tipis',
'Api lampu tertutup','Api lampu tertutup','Air sungai langit','Air sungai langit',
'Tanah pos besar','Tanah pos besar','Emas perhiasan','Emas perhiasan',
'Kayu murbei','Kayu murbei','Air sungai besar','Air sungai besar',
'Tanah dalam pasir','Tanah dalam pasir','Api di atas langit','Api di atas langit',
'Kayu pohon delima','Kayu pohon delima','Air laut besar','Air laut besar'
];

// Hidden stems for DISPLAY (正气 first - main stem shown first)
$HS_DISPLAY = [
 0=>[9],           // 子: 癸
 1=>[5,9,7],       // 丑: 己癸辛
 2=>[0,2,4],       // 寅: 甲丙戊
 3=>[1],           // 卯: 乙
 4=>[4,1,9],       // 辰: 戊乙癸
 5=>[2,4,6],       // 巳: 丙戊庚
 6=>[3,5],         // 午: 丁己
 7=>[5,3,1],       // 未: 己丁乙
 8=>[6,4,8],       // 申: 庚戊壬
 9=>[7],           // 酉: 辛
10=>[4,7,3],       // 戌: 戊辛丁
11=>[8,0],         // 亥: 壬甲 (NO 丙)
];

// Hidden stems for SI LING calculation (余气→中气→正气, time accumulation order)
$HS = [
 0=>[[9,30]],                    // 子: 癸
 1=>[[9,9],[5,3],[7,18]],        // 丑: 癸9,己3,辛18
 2=>[[4,7],[2,3],[0,20]],        // 寅: 戊7,丙3,甲20
 3=>[[1,30]],                    // 卯: 乙
 4=>[[9,3],[1,9],[4,18]],        // 辰: 癸3,乙9,戊18
 5=>[[6,7],[4,3],[2,20]],        // 巳: 庚7,戊3,丙20
 6=>[[5,10],[3,20]],             // 午: 己10,丁20
 7=>[[5,9],[3,3],[1,18]],        // 未: 己9,丁3,乙18
 8=>[[8,7],[4,3],[6,20]],        // 申: 壬7,戊3,庚20
 9=>[[7,30]],                    // 酉: 辛
10=>[[3,9],[7,3],[4,18]],        // 戌: 丁9,辛3,戊18
11=>[[0,3],[8,27]],              // 亥: 甲3,壬27 (NO 丙)
];