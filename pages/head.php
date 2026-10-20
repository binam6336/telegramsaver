/* ===== Sidebar User Footer ===== */
.sidebar-user{
border-top:1px solid rgba(255,255,255,.15);
padding:14px 16px 16px;
margin-top:auto;
background:rgba(0,0,0,.08);
border-radius:12px 12px 0 0;
}
.sidebar-user-info{
display:flex;align-items:center;gap:9px;
color:#fff;font-size:.85rem;font-weight:600;
margin-bottom:10px;
overflow:hidden;
white-space:nowrap;text-overflow:ellipsis;
}
.sidebar-user-info i{font-size:1.4rem;opacity:.85;flex-shrink:0}
.sidebar-user-info span{overflow:hidden;text-overflow:ellipsis}

.sidebar-logout{
display:flex;align-items:center;gap:8px;
padding:9px 12px;border-radius:9px;
background:rgba(239,68,68,.18);
color:#ffd6d6 !important;
text-decoration:none;
font-size:.8rem;font-weight:700;
transition:background-color .2s,color .2s,transform .1s;
}
.sidebar-logout:hover{
background:rgba(239,68,68,.35);
color:#fff !important;
}
.sidebar-logout:active{transform:scale(.97)}
.sidebar-logout i{font-size:.9rem}