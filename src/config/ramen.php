<?php

return [
    'reserved_parameter' => explode(",", env('RAMEN_RESERVED_PARAMETER', implode(",", ['limit', 'relation', 'page', 'orderBy', 'soft', '_', 'cursor', 'random']))),
    'reserved_datatable' => explode(",", env('RAMEN_RESERVED_DATATABLE', implode(",", ['length', 'start', 'draw', 'datatables', '_']))),
    'reserved_datatable_process' => explode(",", env('RAMEN_RESERVED_DATATABLE_PROCESS', implode(",", ['columns', 'order', 'relation', 'search']))),
    'reserved_datatable_detail' => false,
    'trace_detail' => env('RAMEN_TRACE', false),
    'debug' => env('RAMEN_DEBUG', false)
];
