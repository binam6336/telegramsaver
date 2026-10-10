<?php
$title = $title ?? 'پنل مدیریت';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0
        }

        body {
            font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
            background: #f1f5f9;
            color: #1e293b;
            direction: rtl
        }

        .layout {
            display: flex;
            min-height: 100vh
        }

        /* ===== Sidebar ===== */
        .sidebar {
            width: 260px;
            flex-shrink: 0;
            background: linear-gradient(180deg, #4c8bf5 0%, #2f6bdc 100%);
            color: #fff;
            display: flex;
            flex-direction: column
        }

        .sidebar-scroll {
            padding: 22px 16px 18px;
            display: flex;
            flex-direction: column;
            gap: 4px;
            flex: 1;
            overflow-y: auto
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1rem;
            font-weight: 800;
            padding: 0 8px 20px;
            margin-bottom: 6px;
            border-bottom: 1px solid rgba(255, 255, 255, .15)
        }

        .sidebar-brand i {
            width: 34px;
            height: 34px;
            background: rgba(255, 255, 255, .22);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 11px 13px;
            color: rgba(255, 255, 255, .82);
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: .87rem;
            transition: background-color .25s, color .25s
        }

        .menu-item:hover {
            background: rgba(255, 255, 255, .14);
            color: #fff
        }

        .menu-item.active {
            background: rgba(255, 255, 255, .22);
            color: #fff
        }

        .menu-item i {
            width: 18px;
            text-align: center
        }

        /* ===== Content ===== */
        .content {
            flex: 1;
            padding: 28px 32px;
            overflow-x: hidden
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 20px;
            flex-wrap: wrap
        }

        .page-header h1 {
            font-size: 1.3rem;
            font-weight: 700
        }

        .search-box {
            position: relative;
            min-width: 280px
        }

        .search-box i {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            pointer-events: none
        }

        .search-box input {
            width: 100%;
            padding: 11px 42px 11px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #fff;
            font-family: inherit;
            font-size: .88rem;
            outline: none;
            transition: border-color .2s, box-shadow .2s
        }

        .search-box input:focus {
            border-color: #4c8bf5;
            box-shadow: 0 0 0 3px rgba(76, 139, 245, .15)
        }

        /* ===== Table ===== */
        .table-wrap {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 2px 12px rgba(15, 23, 42, .06);
            overflow: hidden
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: .87rem
        }

        .data-table th {
            background: #f8fafc;
            color: #64748b;
            font-weight: 700;
            text-align: right;
            padding: 13px 16px;
            border-bottom: 1px solid #e2e8f0;
            font-size: .8rem;
            letter-spacing: .3px
        }

        .data-table td {
            padding: 13px 16px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle
        }

        .data-table tr:last-child td {
            border-bottom: 0
        }

        .data-table tr:hover td {
            background: #f8fafc
        }

        .cell-text {
            max-width: 380px;
            color: #475569
        }

        .loading,
        .empty,
        .error {
            text-align: center;
            color: #94a3b8;
            padding: 28px !important
        }

        .error {
            color: #dc2626
        }

        /* ===== Buttons ===== */
        .btn-view {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 8px;
            background: #4c8bf5;
            color: #fff;
            text-decoration: none;
            font-size: .78rem;
            font-weight: 600;
            transition: background-color .2s;
            border: 0;
            cursor: pointer
        }

        .btn-view:hover {
            background: #2f6bdc
        }

        .btn-view.disabled {
            background: #e2e8f0;
            color: #94a3b8;
            cursor: not-allowed;
            pointer-events: none
        }

        /* ===== Pagination ===== */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 6px;
            margin-top: 20px;
            flex-wrap: wrap
        }

        .page-btn {
            min-width: 38px;
            padding: 8px 12px;
            border: 1px solid #e2e8f0;
            background: #fff;
            color: #475569;
            border-radius: 8px;
            cursor: pointer;
            font-family: inherit;
            font-size: .82rem;
            font-weight: 600;
            transition: all .2s
        }

        .page-btn:hover {
            border-color: #4c8bf5;
            color: #4c8bf5
        }

        .page-btn.active {
            background: #4c8bf5;
            border-color: #4c8bf5;
            color: #fff
        }

        @media (max-width:768px) {
            .sidebar {
                width: 72px
            }

            .sidebar-brand span,
            .menu-item span {
                display: none
            }

            .content {
                padding: 20px 14px
            }

            .cell-text {
                max-width: 160px
            }
        }
    </style>
</head>

<body>
    <div class="layout">