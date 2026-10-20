<?php
require_once __DIR__ . '/auth.php';
auth_boot();

$title = $title ?? 'پنل مدیریت';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title) ?></title>

    <!-- ═══ Favicon ═══ -->
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="../favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="../favicon.ico">
    <meta name="theme-color" content="#4c8bf5">

    <!-- ═══ Font Awesome ═══ -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        /* ═══════════════════════════════════════════════════════════
   RESET + BASE
═══════════════════════════════════════════════════════════ */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html,
        body {
            height: 100%;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
            background: #f1f5f9;
            color: #1e293b;
            direction: rtl;
            font-size: 15px;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        a {
            color: inherit;
        }

        button {
            font-family: inherit;
        }

        img {
            max-width: 100%;
            display: block;
        }

        /* ═══════════════════════════════════════════════════════════
   LAYOUT
═══════════════════════════════════════════════════════════ */
        .layout {
            display: flex;
            min-height: 100vh;
        }

        /* ═══════════════════════════════════════════════════════════
   SIDEBAR
═══════════════════════════════════════════════════════════ */
        .sidebar {
            width: 260px;
            flex-shrink: 0;
            background: linear-gradient(180deg, #4c8bf5 0%, #2f6bdc 100%);
            color: #fff;
            display: flex;
            flex-direction: column;
            position: sticky;
            top: 0;
            height: 100vh;
            box-shadow: -2px 0 12px rgba(76, 139, 245, 0.15);
        }

        .sidebar-scroll {
            flex: 1;
            min-height: 0;
            padding: 22px 16px 12px;
            display: flex;
            flex-direction: column;
            gap: 4px;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .sidebar-scroll::-webkit-scrollbar {
            width: 5px;
        }

        .sidebar-scroll::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar-scroll::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.18);
            border-radius: 10px;
        }

        .sidebar-scroll::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.28);
        }

        /* ─── Brand ─── */
        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1rem;
            font-weight: 800;
            color: #fff;
            padding: 0 8px 18px;
            margin-bottom: 8px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
            letter-spacing: -0.3px;
            flex-shrink: 0;
        }

        .sidebar-brand i {
            width: 34px;
            height: 34px;
            background: rgba(255, 255, 255, 0.22);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            flex-shrink: 0;
        }

        /* ─── Menu items ─── */
        .menu-item {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 11px 13px;
            color: rgba(255, 255, 255, 0.82);
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.87rem;
            transition: background-color 0.25s, color 0.25s;
            flex-shrink: 0;
        }

        .menu-item:hover {
            background: rgba(255, 255, 255, 0.14);
            color: #fff;
        }

        .menu-item.active {
            background: rgba(255, 255, 255, 0.22);
            color: #fff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .menu-item i {
            width: 18px;
            text-align: center;
            font-size: 0.92rem;
            flex-shrink: 0;
        }

        /* ─── User Footer ─── */
        .sidebar-user {
            flex-shrink: 0;
            border-top: 1px solid rgba(255, 255, 255, 0.15);
            padding: 14px 16px 16px;
            background: rgba(0, 0, 0, 0.08);
        }

        .sidebar-user-info {
            display: flex;
            align-items: center;
            gap: 9px;
            color: #fff;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 10px;
            overflow: hidden;
        }

        .sidebar-user-info i {
            font-size: 1.4rem;
            opacity: 0.85;
            flex-shrink: 0;
        }

        .sidebar-user-info span {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .sidebar-logout {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 9px 12px;
            border-radius: 9px;
            background: rgba(239, 68, 68, 0.18);
            color: #ffd6d6 !important;
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 700;
            transition: background-color 0.2s, color 0.2s, transform 0.1s;
        }

        .sidebar-logout:hover {
            background: rgba(239, 68, 68, 0.35);
            color: #fff !important;
        }

        .sidebar-logout:active {
            transform: scale(0.97);
        }

        .sidebar-logout i {
            font-size: 0.9rem;
        }

        /* ═══════════════════════════════════════════════════════════
   CONTENT
═══════════════════════════════════════════════════════════ */
        .content {
            flex: 1;
            min-width: 0;
            padding: 28px 32px;
            overflow-x: hidden;
        }

        /* ─── Page header ─── */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .page-header h1 {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1e293b;
        }

        /* ═══════════════════════════════════════════════════════════
   TABLE
═══════════════════════════════════════════════════════════ */
        .table-wrap {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 2px 12px rgba(15, 23, 42, 0.06);
            overflow: hidden;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.87rem;
        }

        .data-table th {
            background: #f8fafc;
            color: #64748b;
            font-weight: 700;
            text-align: right;
            padding: 13px 16px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.8rem;
            letter-spacing: 0.3px;
        }

        .data-table td {
            padding: 13px 16px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .data-table tr:last-child td {
            border-bottom: 0;
        }

        .data-table tr:hover td {
            background: #f8fafc;
        }

        .cell-text {
            max-width: 380px;
            color: #475569;
        }

        /* ─── States ─── */
        .loading,
        .empty,
        .error {
            text-align: center;
            padding: 28px !important;
            color: #94a3b8;
            font-size: 0.85rem;
        }

        .error {
            color: #dc2626;
        }

        /* ═══════════════════════════════════════════════════════════
   BUTTONS
═══════════════════════════════════════════════════════════ */
        .btn-view {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 8px;
            background: #4c8bf5;
            color: #fff;
            text-decoration: none;
            font-size: 0.78rem;
            font-weight: 600;
            transition: background-color 0.2s, transform 0.1s;
            border: 0;
            cursor: pointer;
        }

        .btn-view:hover {
            background: #2f6bdc;
        }

        .btn-view:active {
            transform: scale(0.97);
        }

        .btn-view.disabled {
            background: #e2e8f0;
            color: #94a3b8;
            cursor: not-allowed;
            pointer-events: none;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 20px;
            border: 0;
            border-radius: 10px;
            background: linear-gradient(135deg, #4c8bf5 0%, #2f6bdc 100%);
            color: #fff;
            font-family: inherit;
            font-size: 0.85rem;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 3px 12px rgba(76, 139, 245, 0.28);
            transition: opacity 0.2s, transform 0.1s;
            white-space: nowrap;
        }

        .btn-primary:hover:not(:disabled) {
            opacity: 0.92;
        }

        .btn-primary:active:not(:disabled) {
            transform: scale(0.97);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-refresh {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 18px;
            border: 0;
            border-radius: 10px;
            background: linear-gradient(135deg, #4c8bf5 0%, #2f6bdc 100%);
            color: #fff;
            font-family: inherit;
            font-size: 0.85rem;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 3px 12px rgba(76, 139, 245, 0.28);
            transition: opacity 0.2s, transform 0.1s, box-shadow 0.2s;
        }

        .btn-refresh:hover:not(:disabled) {
            opacity: 0.92;
            box-shadow: 0 4px 16px rgba(76, 139, 245, 0.4);
        }

        .btn-refresh:active:not(:disabled) {
            transform: scale(0.97);
        }

        .btn-refresh:disabled {
            opacity: 0.65;
            cursor: not-allowed;
        }

        .btn-refresh .fa-sync-alt.is-spinning {
            animation: spin 1s linear infinite;
        }

        .btn-create-tasks {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 18px;
            border: 0;
            border-radius: 10px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #fff;
            font-family: inherit;
            font-size: 0.85rem;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 3px 12px rgba(16, 185, 129, 0.28);
            transition: opacity 0.2s, transform 0.1s, box-shadow 0.2s;
        }

        .btn-create-tasks:hover:not(:disabled) {
            opacity: 0.92;
            box-shadow: 0 4px 16px rgba(16, 185, 129, 0.4);
        }

        .btn-create-tasks:active:not(:disabled) {
            transform: scale(0.97);
        }

        .btn-create-tasks:disabled {
            opacity: 0.65;
            cursor: not-allowed;
        }

        .btn-create-tasks .fa-list-check.is-spinning {
            animation: spin 1s linear infinite;
        }

        .btn-trash {
            width: 32px;
            height: 32px;
            border: 0;
            background: #fee2e2;
            color: #dc2626;
            border-radius: 8px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.2s, color 0.2s, transform 0.1s;
        }

        .btn-trash:hover {
            background: #fecaca;
            color: #991b1b;
        }

        .btn-trash:active {
            transform: scale(0.94);
        }

        /* ─── Action cell (blue/green/red row buttons) ─── */
        .action-cell {
            display: flex;
            gap: 6px;
            align-items: center;
            flex-wrap: nowrap;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 7px 11px;
            border-radius: 8px;
            border: 0;
            cursor: pointer;
            font-family: inherit;
            font-size: 0.75rem;
            font-weight: 700;
            text-decoration: none;
            color: #fff;
            transition: opacity 0.2s, background-color 0.2s, transform 0.1s;
            white-space: nowrap;
        }

        .btn-action:hover {
            opacity: 0.9;
        }

        .btn-action:active {
            transform: scale(0.96);
        }

        .btn-action.blue {
            background: #4c8bf5;
        }

        .btn-action.green {
            background: #10b981;
        }

        .btn-action.green.is-active {
            background: #059669;
            box-shadow: inset 0 0 0 2px rgba(255, 255, 255, 0.35);
        }

        .btn-action.red {
            background: #ef4444;
        }

        .btn-action.red.is-active {
            background: #b91c1c;
            box-shadow: inset 0 0 0 2px rgba(255, 255, 255, 0.35);
        }

        .btn-action.disabled,
        .btn-action:disabled {
            background: #e2e8f0;
            color: #94a3b8;
            cursor: not-allowed;
            pointer-events: none;
            opacity: 1;
        }

        @keyframes spin {
            from {
                transform: rotate(0);
            }

            to {
                transform: rotate(360deg);
            }
        }

        /* ═══════════════════════════════════════════════════════════
   BADGES
═══════════════════════════════════════════════════════════ */
        .badge-keyword {
            display: inline-block;
            padding: 3px 9px;
            background: #eef4ff;
            color: #2f6bdc;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .badge-group {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 10px;
            background: #f0f9ff;
            color: #0369a1;
            border: 0;
            border-radius: 6px;
            font-family: inherit;
            font-size: 0.75rem;
            font-weight: 700;
            cursor: pointer;
            transition: background-color 0.2s, transform 0.1s;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .badge-group:hover {
            background: #e0f2fe;
        }

        .badge-group:active {
            transform: scale(0.97);
        }

        .badge-group i {
            font-size: 0.68rem;
            opacity: 0.75;
        }

        .badge-group.is-empty {
            background: #f1f5f9;
            color: #94a3b8;
            cursor: default;
            pointer-events: none;
        }

        .badge-sender {
            display: inline-block;
            padding: 3px 9px;
            background: #f5f3ff;
            color: #6d28d9;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .badge-clicks {
            background: rgba(255, 255, 255, 0.28);
            padding: 1px 7px;
            border-radius: 10px;
            font-size: 0.7rem;
            font-weight: 700;
            min-width: 20px;
            text-align: center;
        }

        /* ─── Message type badges ─── */
        .badge-type {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.73rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .badge-type i {
            font-size: 0.72rem;
        }

        .badge-type.type-text {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .badge-type.type-photo {
            background: #f0fdf4;
            color: #15803d;
        }

        .badge-type.type-video {
            background: #fef2f2;
            color: #b91c1c;
        }

        .badge-type.type-voice {
            background: #faf5ff;
            color: #7e22ce;
        }

        .badge-type.type-audio {
            background: #fef3c7;
            color: #b45309;
        }

        .badge-type.type-document {
            background: #eef2ff;
            color: #4338ca;
        }

        .badge-type.type-sticker {
            background: #fdf2f8;
            color: #be185d;
        }

        .badge-type.type-animation {
            background: #ecfeff;
            color: #0891b2;
        }

        .badge-type.type-poll {
            background: #f0fdfa;
            color: #0f766e;
        }

        .badge-type.type-location {
            background: #fff7ed;
            color: #c2410c;
        }

        .badge-type.type-contact {
            background: #f7fee7;
            color: #4d7c0f;
        }

        .badge-type.type-unknown {
            background: #f1f5f9;
            color: #64748b;
        }

        /* ═══════════════════════════════════════════════════════════
   TABS
═══════════════════════════════════════════════════════════ */
        .tabs {
            display: flex;
            gap: 4px;
            margin-bottom: 16px;
            border-bottom: 1px solid #e2e8f0;
        }

        .tab {
            padding: 11px 18px;
            border: 0;
            background: transparent;
            cursor: pointer;
            font-family: inherit;
            font-size: 0.85rem;
            font-weight: 600;
            color: #64748b;
            border-bottom: 2px solid transparent;
            transition: color 0.2s, border-color 0.2s;
            position: relative;
            top: 1px;
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .tab:hover {
            color: #4c8bf5;
        }

        .tab.active {
            color: #4c8bf5;
            border-bottom-color: #4c8bf5;
        }

        .tab .count {
            background: #e2e8f0;
            color: #475569;
            font-size: 0.7rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 10px;
            min-width: 22px;
            text-align: center;
        }

        .tab.active .count {
            background: #dbeafe;
            color: #1d4ed8;
        }

        /* ═══════════════════════════════════════════════════════════
   FORMS
═══════════════════════════════════════════════════════════ */
        .search-box {
            position: relative;
            min-width: 280px;
        }

        .search-box i {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            pointer-events: none;
        }

        .search-box input {
            width: 100%;
            padding: 11px 42px 11px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #fff;
            font-family: inherit;
            font-size: 0.88rem;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            color: #1e293b;
        }

        .search-box input:focus {
            border-color: #4c8bf5;
            box-shadow: 0 0 0 3px rgba(76, 139, 245, 0.15);
        }

        .form-select {
            padding: 11px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #fff;
            font-family: inherit;
            font-size: 0.85rem;
            color: #334155;
            outline: none;
            cursor: pointer;
            transition: border-color 0.2s;
        }

        .form-select:focus {
            border-color: #4c8bf5;
            box-shadow: 0 0 0 3px rgba(76, 139, 245, 0.15);
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .field label {
            font-size: 0.78rem;
            font-weight: 700;
            color: #475569;
        }

        .field .req {
            color: #dc2626;
        }

        .field input,
        .field select,
        .field textarea {
            padding: 11px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-family: inherit;
            font-size: 0.87rem;
            color: #1e293b;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            background: #fff;
        }

        .field input:focus,
        .field select:focus,
        .field textarea:focus {
            border-color: #4c8bf5;
            box-shadow: 0 0 0 3px rgba(76, 139, 245, 0.15);
        }

        .field .hint {
            font-size: 0.72rem;
            color: #94a3b8;
            line-height: 1.5;
        }

        .field .hint code {
            background: #f1f5f9;
            padding: 1px 5px;
            border-radius: 4px;
            font-size: 0.72rem;
            color: #be185d;
        }

        /* ═══════════════════════════════════════════════════════════
   PAGINATION
═══════════════════════════════════════════════════════════ */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 6px;
            margin-top: 20px;
            flex-wrap: wrap;
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
            font-size: 0.82rem;
            font-weight: 600;
            transition: all 0.2s;
        }

        .page-btn:hover {
            border-color: #4c8bf5;
            color: #4c8bf5;
        }

        .page-btn.active {
            background: #4c8bf5;
            border-color: #4c8bf5;
            color: #fff;
        }

        /* ═══════════════════════════════════════════════════════════
   TOAST
═══════════════════════════════════════════════════════════ */
        .toast {
            position: fixed;
            bottom: 24px;
            left: 50%;
            transform: translateX(-50%) translateY(20px);
            background: #1e293b;
            color: #fff;
            padding: 11px 20px;
            border-radius: 10px;
            font-size: 0.83rem;
            font-weight: 600;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
            opacity: 0;
            transition: opacity 0.3s, transform 0.3s;
            z-index: 99999;
            pointer-events: none;
            max-width: 80vw;
        }

        .toast.show {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        .toast.success {
            background: #059669;
        }

        .toast.error {
            background: #dc2626;
        }

        /* ═══════════════════════════════════════════════════════════
   MODAL
═══════════════════════════════════════════════════════════ */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.55);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            z-index: 10000;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: opacity 0.2s ease, visibility 0s linear 0.2s;
        }

        .modal-overlay.is-open {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
            transition: opacity 0.2s ease, visibility 0s linear 0s;
        }

        .modal-box {
            background: #fff;
            border-radius: 14px;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            transform: scale(0.94);
            transition: transform 0.2s cubic-bezier(0.25, 1, 0.5, 1);
        }

        .modal-overlay.is-open .modal-box {
            transform: scale(1);
        }

        .modal-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            border-bottom: 1px solid #e2e8f0;
        }

        .modal-head h3 {
            font-size: 0.95rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 9px;
            color: #1e293b;
        }

        .modal-close {
            width: 32px;
            height: 32px;
            border: 0;
            background: #f1f5f9;
            color: #64748b;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.2s, color 0.2s;
        }

        .modal-close:hover {
            background: #e2e8f0;
            color: #1e293b;
        }

        .modal-body {
            padding: 20px;
        }

        .info-row {
            margin-bottom: 16px;
        }

        .info-row:last-child {
            margin-bottom: 0;
        }

        .info-label {
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 6px;
            letter-spacing: 0.3px;
        }

        .info-value {
            display: block;
            font-size: 0.9rem;
            color: #1e293b;
            font-weight: 600;
            padding: 10px 12px;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .info-value-row {
            display: flex;
            gap: 8px;
            align-items: stretch;
        }

        .info-code {
            flex: 1;
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
            color: #be185d;
            background: #fdf2f8;
            border: 1px solid #fbcfe8;
            padding: 10px 12px;
            border-radius: 8px;
            direction: ltr;
            text-align: left;
            user-select: all;
            word-break: break-all;
        }

        .btn-copy {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 0 14px;
            border: 0;
            background: #4c8bf5;
            color: #fff;
            border-radius: 8px;
            cursor: pointer;
            font-family: inherit;
            font-size: 0.8rem;
            font-weight: 700;
            transition: background-color 0.2s, transform 0.1s;
            white-space: nowrap;
        }

        .btn-copy:hover {
            background: #2f6bdc;
        }

        .btn-copy:active {
            transform: scale(0.96);
        }

        .btn-copy.copied {
            background: #10b981;
        }

        /* ═══════════════════════════════════════════════════════════
   ALERT
═══════════════════════════════════════════════════════════ */
        .alert {
            margin-bottom: 16px;
            padding: 11px 16px;
            border-radius: 10px;
            font-size: 0.83rem;
            font-weight: 600;
            animation: fadeIn 0.25s ease;
        }

        .alert.success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .alert.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-4px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ═══════════════════════════════════════════════════════════
   DASHBOARD — Stats Cards
═══════════════════════════════════════════════════════════ */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 14px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: #fff;
            border-radius: 14px;
            padding: 18px 20px;
            display: flex;
            align-items: center;
            gap: 14px;
            box-shadow: 0 2px 12px rgba(15, 23, 42, 0.06);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(15, 23, 42, 0.1);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            flex-shrink: 0;
        }

        .stat-icon.blue {
            background: #eef4ff;
            color: #2f6bdc;
        }

        .stat-icon.green {
            background: #ecfdf5;
            color: #059669;
        }

        .stat-icon.red {
            background: #fef2f2;
            color: #dc2626;
        }

        .stat-icon.purple {
            background: #f5f3ff;
            color: #7c3aed;
        }

        .stat-icon.amber {
            background: #fffbeb;
            color: #d97706;
        }

        .stat-icon.cyan {
            background: #ecfeff;
            color: #0891b2;
        }

        .stat-value {
            font-size: 1.4rem;
            font-weight: 800;
            color: #1e293b;
            line-height: 1;
        }

        .stat-label {
            font-size: 0.75rem;
            color: #64748b;
            font-weight: 600;
            margin-top: 4px;
        }

        /* ═══════════════════════════════════════════════════════════
   DASHBOARD — Sections
═══════════════════════════════════════════════════════════ */
        .dashboard-section {
            margin-bottom: 28px;
        }

        .section-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e2e8f0;
        }

        .section-title i {
            font-size: 1rem;
        }

        .section-subtitle {
            font-size: 0.75rem;
            color: #94a3b8;
            font-weight: 500;
            margin-right: auto;
        }

        /* ─── Trend filters ─── */
        .trend-filters {
            display: flex;
            gap: 6px;
            margin-bottom: 14px;
            flex-wrap: wrap;
        }

        .trend-filter {
            padding: 8px 16px;
            border: 1px solid #e2e8f0;
            background: #fff;
            color: #475569;
            border-radius: 8px;
            font-family: inherit;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .trend-filter:hover {
            border-color: #4c8bf5;
            color: #4c8bf5;
        }

        .trend-filter.active {
            background: #4c8bf5;
            border-color: #4c8bf5;
            color: #fff;
            box-shadow: 0 2px 8px rgba(76, 139, 245, 0.3);
        }

        .trend-filter:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* ─── Trend chart ─── */
        .trend-chart {
            position: relative;
            min-height: 240px;
        }

        .trend-chart svg {
            display: block;
            width: 100%;
            height: auto;
            overflow: visible;
        }

        .trend-chart .trend-summary {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            gap: 10px;
            flex-wrap: wrap;
        }

        .trend-chart .trend-total {
            font-size: 0.85rem;
            color: #475569;
            font-weight: 600;
        }

        .trend-chart .trend-total strong {
            color: #4c8bf5;
            font-size: 1.05rem;
        }

        .trend-chart .trend-peak {
            font-size: 0.78rem;
            color: #94a3b8;
            font-weight: 600;
        }

        .trend-chart circle {
            cursor: pointer;
            transition: r 0.15s;
        }

        .trend-chart circle:hover {
            r: 6;
        }

        /* ─── Keyword cards ─── */
        .keyword-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
            gap: 16px;
        }

        .keyword-card {
            background: #fff;
            border-radius: 14px;
            padding: 16px 18px;
            box-shadow: 0 2px 12px rgba(15, 23, 42, 0.06);
            border-top: 3px solid #4c8bf5;
            transition: transform 0.2s;
        }

        .keyword-card:hover {
            transform: translateY(-2px);
        }

        .keyword-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
            gap: 10px;
            flex-wrap: wrap;
        }

        .keyword-total {
            font-size: 0.75rem;
            color: #64748b;
            font-weight: 600;
            background: #f1f5f9;
            padding: 3px 10px;
            border-radius: 10px;
        }

        /* ─── Bar list ─── */
        .bar-list {
            display: flex;
            flex-direction: column;
            gap: 11px;
        }

        .bar-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 4px;
            gap: 8px;
        }

        .bar-label {
            font-size: 0.78rem;
            color: #475569;
            font-weight: 600;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            flex: 1;
            min-width: 0;
        }

        .bar-value {
            font-size: 0.75rem;
            color: #1e293b;
            font-weight: 700;
            flex-shrink: 0;
            background: #f8fafc;
            padding: 1px 8px;
            border-radius: 8px;
        }

        .bar-track {
            background: #f1f5f9;
            border-radius: 4px;
            height: 8px;
            overflow: hidden;
        }

        .bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #4c8bf5, #2f6bdc);
            border-radius: 4px;
            transition: width 0.6s cubic-bezier(0.25, 1, 0.5, 1);
            min-width: 4px;
        }

        .bar-fill.red {
            background: linear-gradient(90deg, #ef4444, #dc2626);
        }

        .bar-fill.green {
            background: linear-gradient(90deg, #10b981, #059669);
        }

        .bar-fill.purple {
            background: linear-gradient(90deg, #8b5cf6, #7c3aed);
        }

        .bar-meta {
            font-size: 0.68rem;
            color: #94a3b8;
            font-weight: 600;
            margin-right: 6px;
        }

        /* ─── Daily chart ─── */
        .daily-chart {
            display: flex;
            align-items: flex-end;
            gap: 6px;
            height: 180px;
            padding: 0 4px;
            overflow-x: auto;
        }

        .daily-bar {
            flex: 1;
            min-width: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            height: 100%;
        }

        .daily-bar-track {
            flex: 1;
            width: 100%;
            background: #f1f5f9;
            border-radius: 6px 6px 0 0;
            display: flex;
            align-items: flex-end;
            overflow: hidden;
        }

        .daily-bar-fill {
            width: 100%;
            background: linear-gradient(180deg, #4c8bf5, #2f6bdc);
            border-radius: 6px 6px 0 0;
            transition: height 0.6s ease;
            min-height: 3px;
        }

        .daily-bar-label {
            font-size: 0.65rem;
            color: #64748b;
            font-weight: 600;
            white-space: nowrap;
        }

        .daily-bar-value {
            font-size: 0.68rem;
            color: #4c8bf5;
            font-weight: 700;
        }

        /* ─── Ideas grid ─── */
        .ideas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 14px;
        }

        .idea-card {
            background: #fff;
            border-radius: 12px;
            padding: 16px 18px;
            box-shadow: 0 2px 10px rgba(15, 23, 42, 0.05);
            border-right: 3px solid #f59e0b;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .idea-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(15, 23, 42, 0.1);
        }

        .idea-card i {
            width: 36px;
            height: 36px;
            border-radius: 9px;
            background: #fffbeb;
            color: #d97706;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
            margin-bottom: 10px;
        }

        .idea-card h4 {
            font-size: 0.85rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 6px;
        }

        .idea-card p {
            font-size: 0.75rem;
            color: #64748b;
            line-height: 1.6;
        }

        /* ═══════════════════════════════════════════════════════════
   SETTINGS CARD
═══════════════════════════════════════════════════════════ */
        .settings-card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 2px 12px rgba(15, 23, 42, 0.06);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .card-head {
            padding: 20px 24px;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
        }

        .card-title {
            display: flex;
            gap: 14px;
            align-items: flex-start;
        }

        .card-title>i {
            width: 42px;
            height: 42px;
            border-radius: 11px;
            background: #fee2e2;
            color: #dc2626;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.05rem;
            flex-shrink: 0;
        }

        .card-title h2 {
            font-size: 1rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 4px;
        }

        .card-title p {
            font-size: 0.82rem;
            color: #64748b;
            line-height: 1.6;
        }

        .card-body {
            padding: 24px;
        }

        /* ─── Mini table (settings) ─── */
        .table-mini {
            overflow: hidden;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
        }

        .table-mini table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }

        .table-mini th {
            background: #f8fafc;
            color: #64748b;
            font-weight: 700;
            text-align: right;
            padding: 11px 14px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.78rem;
        }

        .table-mini td {
            padding: 11px 14px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .table-mini tr:last-child td {
            border-bottom: 0;
        }

        .table-mini tr:hover td {
            background: #f8fafc;
        }

        .table-mini .loading,
        .table-mini .empty {
            text-align: center;
            color: #94a3b8;
            padding: 24px !important;
        }

        .table-mini .error {
            color: #dc2626;
            text-align: center;
            padding: 24px !important;
        }

        .chat-id-code {
            font-family: 'Courier New', monospace;
            font-size: 0.83rem;
            color: #be185d;
            background: #fdf2f8;
            padding: 4px 10px;
            border-radius: 6px;
            border: 1px solid #fbcfe8;
            direction: ltr;
            display: inline-block;
            user-select: all;
        }

        .group-name-cell {
            color: #334155;
            font-weight: 500;
        }

        .group-name-cell.empty {
            color: #94a3b8;
            font-style: italic;
        }

        /* ═══════════════════════════════════════════════════════════
   SKELETON / STATES
═══════════════════════════════════════════════════════════ */
        .skeleton {
            color: #94a3b8;
            text-align: center;
            padding: 32px 16px;
            font-size: 0.85rem;
        }

        .empty-state {
            color: #94a3b8;
            text-align: center;
            padding: 36px 16px;
            font-size: 0.85rem;
        }

        .error-state {
            color: #dc2626;
            text-align: center;
            padding: 32px 16px;
            font-size: 0.85rem;
        }

        /* ═══════════════════════════════════════════════════════════
   RESPONSIVE
═══════════════════════════════════════════════════════════ */
        @media (max-width: 820px) {
            .block-form {
                grid-template-columns: 1fr;
            }

            .card-body {
                padding: 18px;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 72px;
            }

            .sidebar-brand span,
            .menu-item span,
            .sidebar-user-info span,
            .sidebar-logout span {
                display: none;
            }

            .sidebar-brand {
                justify-content: center;
                padding: 0 0 18px;
            }

            .menu-item {
                justify-content: center;
                padding: 11px;
            }

            .sidebar-user {
                padding: 12px 8px;
            }

            .sidebar-user-info {
                justify-content: center;
                margin-bottom: 8px;
            }

            .sidebar-logout {
                justify-content: center;
                padding: 9px;
            }

            .content {
                padding: 20px 14px;
            }

            .cell-text {
                max-width: 160px;
            }

            .search-box {
                min-width: 0;
                width: 100%;
            }

            .page-header h1 {
                font-size: 1.15rem;
            }
        }

        @media (max-width: 600px) {
            .modal-box {
                max-width: 100%;
            }

            .info-value-row {
                flex-direction: column;
            }

            .btn-copy {
                padding: 11px 14px;
                justify-content: center;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .stat-card {
                padding: 14px;
            }

            .stat-icon {
                width: 40px;
                height: 40px;
            }

            .stat-value {
                font-size: 1.15rem;
            }

            .trend-filter {
                padding: 7px 12px;
                font-size: 0.75rem;
            }
        }
    </style>
</head>

<body>
    <div class="layout">