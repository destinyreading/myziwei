// ============================================================
// Ba Zi lookup tables.
//
// Ported verbatim from Bambang's own PHP calculator
// (reference/bazical date 7 sep 2026/api/bazi_data.php), whose output he has
// verified. Do not "correct" these values against other sources — differences
// between schools are deliberate here.
//
// Index conventions used everywhere in lib/bazi:
//   stem (TG) 0..9  = 甲..癸
//   branch (DZ) 0..11 = 子..亥
//   element 0..4    = 木火土金水
// ============================================================

export const TG: string[] = ["甲", "乙", "丙", "丁", "戊", "己", "庚", "辛", "壬", "癸"];

export const DZ: string[] = ["子", "丑", "寅", "卯", "辰", "巳", "午", "未", "申", "酉", "戌", "亥"];

export const SS: string[] = ["比肩", "劫财", "食神", "伤官", "偏财", "正财", "七杀", "正官", "偏印", "正印"];

export const SS_PY: string[] = ["Bǐ Jiān", "Jié Cái", "Shí Shén", "Shāng Guān", "Piān Cái", "Zhèng Cái", "Qī Shā", "Zhèng Guān", "Piān Yìn", "Zhèng Yìn"];

export const CS_NM: string[] = ["长生", "沐浴", "冠带", "临官", "帝旺", "衰", "病", "死", "墓", "绝", "胎", "养"];

export const CS_PY: string[] = ["Cháng Shēng", "Mù Yù", "Guān Dài", "Lín Guān", "Dì Wàng", "Shuāi", "Bìng", "Sǐ", "Mù", "Jué", "Tāi", "Yǎng"];

export const CS_EN: string[] = ["Prosperous Growth", "Bath", "Coming of Age", "Officer", "Prosperous Peak", "Decline", "Sickness", "Death", "Tomb", "Void", "Embryo", "Nourish"];

export const CS_ST: number[] = [11, 6, 2, 9, 2, 9, 5, 0, 8, 3];

export const TG_EL: number[] = [0, 0, 1, 1, 2, 2, 3, 3, 4, 4];

export const DZ_EL: number[] = [4, 2, 0, 0, 2, 1, 1, 2, 3, 3, 2, 4];

export const EL_ZH: string[] = ["木", "火", "土", "金", "水"];

export const EL_NM: string[] = ["Wood", "Fire", "Earth", "Metal", "Water"];

export const SHENG: number[] = [1, 2, 3, 4, 0];

export const KE: number[] = [2, 3, 4, 0, 1];

export const NY: string[] = ["海中金", "海中金", "炉中火", "炉中火", "大林木", "大林木", "路旁土", "路旁土", "剑锋金", "剑锋金", "山头火", "山头火", "涧下水", "涧下水", "城墙土", "城墙土", "白蜡金", "白蜡金", "杨柳木", "杨柳木", "泉中水", "泉中水", "屋上土", "屋上土", "霹雳火", "霹雳火", "松柏木", "松柏木", "长流水", "长流水", "沙中金", "沙中金", "山下火", "山下火", "平地木", "平地木", "壁上土", "壁上土", "金箔金", "金箔金", "覆灯火", "覆灯火", "天河水", "天河水", "大驿土", "大驿土", "钗钏金", "钗钏金", "桑柘木", "桑柘木", "大溪水", "大溪水", "沙中土", "沙中土", "天上火", "天上火", "石榴木", "石榴木", "大海水", "大海水"];

export const NY_PY: string[] = ["Hǎi Zhōng Jīn", "Hǎi Zhōng Jīn", "Lú Zhōng Huǒ", "Lú Zhōng Huǒ", "Dà Lín Mù", "Dà Lín Mù", "Lù Páng Tǔ", "Lù Páng Tǔ", "Jiàn Fēng Jīn", "Jiàn Fēng Jīn", "Shān Tóu Huǒ", "Shān Tóu Huǒ", "Jiàn Xià Shuǐ", "Jiàn Xià Shuǐ", "Chéng Qiáng Tǔ", "Chéng Qiáng Tǔ", "Bái Là Jīn", "Bái Là Jīn", "Yáng Liǔ Mù", "Yáng Liǔ Mù", "Quán Zhōng Shuǐ", "Quán Zhōng Shuǐ", "Wū Shàng Tǔ", "Wū Shàng Tǔ", "Pī Lì Huǒ", "Pī Lì Huǒ", "Sōng Bǎi Mù", "Sōng Bǎi Mù", "Cháng Liú Shuǐ", "Cháng Liú Shuǐ", "Shā Zhōng Jīn", "Shā Zhōng Jīn", "Shān Xià Huǒ", "Shān Xià Huǒ", "Píng Dì Mù", "Píng Dì Mù", "Bì Shàng Tǔ", "Bì Shàng Tǔ", "Jīn Bó Jīn", "Jīn Bó Jīn", "Fù Dēng Huǒ", "Fù Dēng Huǒ", "Tiān Hé Shuǐ", "Tiān Hé Shuǐ", "Dà Yì Tǔ", "Dà Yì Tǔ", "Chāi Chuàn Jīn", "Chāi Chuàn Jīn", "Sāng Zhè Mù", "Sāng Zhè Mù", "Dà Xī Shuǐ", "Dà Xī Shuǐ", "Shā Zhōng Tǔ", "Shā Zhōng Tǔ", "Tiān Shàng Huǒ", "Tiān Shàng Huǒ", "Shí Liú Mù", "Shí Liú Mù", "Dà Hǎi Shuǐ", "Dà Hǎi Shuǐ"];

export const NY_ID: string[] = ["Emas dalam laut", "Emas dalam laut", "Api dalam tungku", "Api dalam tungku", "Kayu hutan besar", "Kayu hutan besar", "Tanah tepi jalan", "Tanah tepi jalan", "Emas ujung pedang", "Emas ujung pedang", "Api puncak gunung", "Api puncak gunung", "Air bawah jurang", "Air bawah jurang", "Tanah tembok kota", "Tanah tembok kota", "Emas lilin putih", "Emas lilin putih", "Kayu pohon willow", "Kayu pohon willow", "Air mata air", "Air mata air", "Tanah atas atap", "Tanah atas atap", "Api petir", "Api petir", "Kayu pinus cemara", "Kayu pinus cemara", "Air mengalir panjang", "Air mengalir panjang", "Emas dalam pasir", "Emas dalam pasir", "Api bawah gunung", "Api bawah gunung", "Kayu tanah datar", "Kayu tanah datar", "Tanah di dinding", "Tanah di dinding", "Emas lembaran tipis", "Emas lembaran tipis", "Api lampu tertutup", "Api lampu tertutup", "Air sungai langit", "Air sungai langit", "Tanah pos besar", "Tanah pos besar", "Emas perhiasan", "Emas perhiasan", "Kayu murbei", "Kayu murbei", "Air sungai besar", "Air sungai besar", "Tanah dalam pasir", "Tanah dalam pasir", "Api di atas langit", "Api di atas langit", "Kayu pohon delima", "Kayu pohon delima", "Air laut besar", "Air laut besar"];

export const HS_DISPLAY: number[][] = [[9], [5, 9, 7], [0, 2, 4], [1], [4, 1, 9], [2, 4, 6], [3, 5], [5, 3, 1], [6, 4, 8], [7], [4, 7, 3], [8, 0]];

export const HS: number[][][] = [[[9, 30]], [[9, 9], [5, 3], [7, 18]], [[4, 7], [2, 3], [0, 20]], [[1, 30]], [[9, 3], [1, 9], [4, 18]], [[6, 7], [4, 3], [2, 20]], [[5, 10], [3, 20]], [[5, 9], [3, 3], [1, 18]], [[8, 7], [4, 3], [6, 20]], [[7, 30]], [[3, 9], [7, 3], [4, 18]], [[0, 3], [8, 27]]];

