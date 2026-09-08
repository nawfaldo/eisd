<?php

/**
 * Money figures for the Makan Bergizi Gratis programme, in rupiah.
 *
 * Nothing here is derivable from the outlet or poisoning tables, so it lives in
 * config rather than the database: these are a handful of national totals, not
 * rows to join against. Replace the values as newer budget documents land.
 */
return [

    /*
    |---------------------------------------------------------------------------
    | Allocations
    |---------------------------------------------------------------------------
    |
    | What the state has put behind the programme, per fiscal year. These are
    | APBN allocations, not audited realisation — realisation is published far
    | later, and the gap runs in both directions, so the site says "allocated".
    |
    */

    'allocations' => [
        // Pagu for the programme's first year; BGN spent Rp 51.5 T of it, 72.5%.
        2025 => [
            'amount' => 71_000_000_000_000,
            'source_url' => 'https://www.antaranews.com/berita/5339457/anggaran-mbg-2025-terserap-725-persen-dari-pagu-rp71-triliun',
        ],
        // Opened at Rp 335 T, cut to Rp 268 T in May 2026. The lower, current figure.
        2026 => [
            'amount' => 268_000_000_000_000,
            'source_url' => 'https://www.bgn.go.id/news/siaran-pers/bgn-kantongi-rp-268-triliun-di-2026-ini-rincian-penggunaannya',
        ],
    ],

];
