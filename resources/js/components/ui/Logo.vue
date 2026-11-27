<script setup>
// Dikho mark: a rounded D whose foot ends in the one marigold dot.
// Variants mirror the brand files in /public:
//   tile  - white D on a navy tile (logo.svg / favicon.svg). The default app icon.
//   dark  - navy D, no background (logo-mark-dark.svg). For light surfaces.
//   light - white D, no background (logo-mark-light.svg). For dark surfaces.
import { computed } from 'vue';

const props = defineProps({
    size: { type: Number, default: 24 },
    name: { type: Boolean, default: true },
    variant: { type: String, default: 'tile', validator: (value) => ['tile', 'dark', 'light'].includes(value) },
});

const NAVY = '#0D1129';
const MARK_PATH =
    'M588 285L568 300L552 321L546 333L539 359L539 1367L541 1380L546 1396L558 1417L575 1435L588 1444L600 1450L616 1455L629 1457L652 1457L664 1455L680 1450L694 1443L715 1426L729 1407L734 1397L741 1372L742 468L978 467L1038 470L1078 475L1141 488L1202 507L1244 524L1297 551L1350 585L1394 620L1427 652L1466 698L1500 749L1522 792L1540 839L1552 885L1560 941L1561 993L1557 1038L1547 1088L1538 1118L1522 1159L1500 1202L1483 1229L1465 1254L1431 1294L1402 1323L1350 1365L1297 1399L1242 1427L1205 1442L1141 1462L1079 1475L1039 1480L1012 1482L877 1483L855 1488L837 1495L819 1506L801 1522L789 1538L782 1552L776 1575L776 1599L782 1621L794 1642L813 1662L838 1678L867 1688L891 1691L1046 1691L1101 1688L1140 1683L1204 1669L1304 1637L1332 1627L1378 1607L1409 1591L1456 1562L1534 1504L1570 1474L1612 1432L1652 1382L1713 1287L1737 1239L1758 1181L1774 1112L1787 1031L1789 1001L1789 962L1784 907L1765 809L1748 753L1729 708L1713 677L1698 652L1652 581L1615 534L1574 492L1515 444L1440 390L1395 364L1353 344L1314 329L1206 294L1174 286L1137 279L1068 272L627 272L612 275Z';
const DOT_PATH =
    'M626 1497L620 1499L615 1499L590 1509L571 1523L559 1536L546 1559L540 1580L540 1587L539 1588L539 1606L540 1607L542 1623L546 1635L552 1647L561 1660L576 1675L589 1684L601 1690L613 1694L628 1697L653 1697L679 1690L693 1683L708 1672L722 1657L728 1648L738 1625L741 1611L741 1600L742 1599L741 1583L735 1560L728 1546L719 1533L701 1516L692 1510L669 1500L655 1497L644 1497L643 1496Z';

/** Bare marks are cropped to the artwork so they sit flush with the wordmark. */
const MARK_BOX = { x: 539, y: 272, width: 1250, height: 1425 };

const isTile = computed(() => props.variant === 'tile');
const viewBox = computed(() => (isTile.value ? '0 0 2000 2000' : `${MARK_BOX.x} ${MARK_BOX.y} ${MARK_BOX.width} ${MARK_BOX.height}`));
const width = computed(() => (isTile.value ? props.size : Math.round((props.size * MARK_BOX.width) / MARK_BOX.height)));
const markFill = computed(() => (props.variant === 'dark' ? NAVY : '#FFFFFF'));
</script>

<template>
    <span class="dk-logo" :class="{ 'on-dark': variant === 'light' }">
        <svg :width="width" :height="size" :viewBox="viewBox" aria-hidden="true">
            <rect v-if="isTile" width="2000" height="2000" rx="440" :fill="NAVY" />
            <path :d="MARK_PATH" :fill="markFill" fill-rule="evenodd" />
            <path :d="DOT_PATH" fill="#FCB52E" fill-rule="evenodd" />
        </svg>
        <span v-if="name">Dikho</span>
    </span>
</template>

<style scoped>
.dk-logo {
    display: inline-flex;
    align-items: center;
    gap: 10px;
}
.dk-logo > span {
    font: 600 16px/1 var(--f);
    letter-spacing: -0.02em;
}
.dk-logo.on-dark > span {
    color: #fff;
}
</style>
