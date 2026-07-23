<?php
// ============================================
// EDUCORE - Global Footer
// ============================================
$isTeacher = isset($teacherFooter) && $teacherFooter;
$isAdmin   = isset($adminFooter)   && $adminFooter;
$isMinimal = isset($minimalFooter) && $minimalFooter;
?>
<!-- ============ FOOTER ============ -->

<?php if ($isTeacher): ?>
<!-- ════════════════════════════════════════════
     TEACHER FOOTER
════════════════════════════════════════════ -->
<footer style="background:#060d1a;margin-top:60px;">

    <!-- ── Main footer body ── -->
    <div class="container" style="padding-top:52px;padding-bottom:0;">

        <!-- Row 1: 3 link columns + stats panel -->
        <div style="display:grid;grid-template-columns:1.3fr 1fr 1fr 1fr 1.3fr;gap:40px;padding-bottom:52px;border-bottom:1px solid rgba(255,255,255,0.07);">

            <!-- ── Brand + description + social ── -->
            <div>
                <!-- Logo -->
                <a href="<?php echo BASE_URL; ?>/Homepage"
                   style="display:inline-flex;align-items:center;gap:10px;text-decoration:none;margin-bottom:18px;">
                    <div style="width:40px;height:40px;background:var(--gradient-primary);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;box-shadow:0 0 16px rgba(34,197,94,0.3);">🎓</div>
                    <span style="font-family:var(--font-heading);font-size:20px;font-weight:900;color:#fff;letter-spacing:-0.3px;">EDU<span style="color:var(--primary);">CORE</span></span>
                </a>

                <!-- Description -->
                <p style="color:var(--text-muted);font-size:14px;line-height:1.75;margin-bottom:22px;max-width:220px;">
                    Empowering teachers worldwide to create engaging courses, manage students, and inspire learning.
                </p>

                <!-- Social icons -->
                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                    <?php foreach ([
                        ['fab fa-facebook-f',  'Facebook'],
                        ['fab fa-twitter',     'Twitter'],
                        ['fab fa-instagram',   'Instagram'],
                        ['fab fa-linkedin-in', 'LinkedIn'],
                        ['fab fa-youtube',     'YouTube'],
                    ] as [$icon, $label]): ?>
                    <a href="#" aria-label="<?php echo $label; ?>"
                       style="width:36px;height:36px;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.08);border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--text-muted);font-size:13px;text-decoration:none;transition:all 0.2s;"
                       onmouseover="this.style.background='var(--gradient-primary)';this.style.borderColor='transparent';this.style.color='#fff';this.style.transform='translateY(-2px)'"
                       onmouseout="this.style.background='rgba(255,255,255,0.05)';this.style.borderColor='rgba(255,255,255,0.08)';this.style.color='var(--text-muted)';this.style.transform='none'">
                        <i class="<?php echo $icon; ?>"></i>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Teaching -->
            <div>
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:22px;">
                    <div style="width:28px;height:28px;background:rgba(34,197,94,0.12);border-radius:7px;display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-chalkboard-teacher" style="font-size:12px;color:var(--primary);"></i>
                    </div>
                    <h5 style="margin:0;font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:2px;color:#fff;">Teaching</h5>
                </div>
                <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:13px;">
                    <?php foreach ([
                        ['?tab=courses',  'fas fa-book',         'My Courses'],
                        ['?tab=create',   'fas fa-plus-circle',  'Create Course'],
                        ['?tab=lessons',  'fas fa-tasks',        'Assignments'],
                        ['?tab=lessons',  'fas fa-brain',        'Quizzes'],
                        ['?tab=overview', 'fas fa-video',        'Live Classes'],
                    ] as [$href, $icon, $label]): ?>
                    <li>
                        <a href="<?php echo BASE_URL; ?>/dashboard/teacher.php<?php echo $href; ?>"
                           style="display:flex;align-items:center;gap:10px;color:var(--text-muted);font-size:14px;text-decoration:none;transition:all 0.2s;padding:4px 0;"
                           onmouseover="this.style.color='var(--primary)';this.querySelector('i').style.color='var(--primary)'"
                           onmouseout="this.style.color='var(--text-muted)';this.querySelector('i').style.color='var(--text-muted)'">
                            <i class="<?php echo $icon; ?>" style="width:14px;font-size:12px;color:var(--text-muted);transition:color 0.2s;"></i>
                            <?php echo $label; ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Students -->
            <div>
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:22px;">
                    <div style="width:28px;height:28px;background:rgba(59,130,246,0.12);border-radius:7px;display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-users" style="font-size:12px;color:#60a5fa;"></i>
                    </div>
                    <h5 style="margin:0;font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:2px;color:#fff;">Students</h5>
                </div>
                <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:13px;">
                    <?php foreach ([
                        ['?tab=students', 'fas fa-chart-line',   'Student Progress'],
                        ['?tab=students', 'fas fa-star-half-alt','Grades'],
                        ['?tab=earnings', 'fas fa-certificate',  'Certificates'],
                        ['?tab=students', 'fas fa-comment-dots', 'Messages'],
                        ['?tab=students', 'fas fa-calendar-check','Attendance'],
                    ] as [$href, $icon, $label]): ?>
                    <li>
                        <a href="<?php echo BASE_URL; ?>/dashboard/teacher.php<?php echo $href; ?>"
                           style="display:flex;align-items:center;gap:10px;color:var(--text-muted);font-size:14px;text-decoration:none;transition:all 0.2s;padding:4px 0;"
                           onmouseover="this.style.color='#60a5fa';this.querySelector('i').style.color='#60a5fa'"
                           onmouseout="this.style.color='var(--text-muted)';this.querySelector('i').style.color='var(--text-muted)'">
                            <i class="<?php echo $icon; ?>" style="width:14px;font-size:12px;color:var(--text-muted);transition:color 0.2s;"></i>
                            <?php echo $label; ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Support -->
            <div>
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:22px;">
                    <div style="width:28px;height:28px;background:rgba(139,92,246,0.12);border-radius:7px;display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-life-ring" style="font-size:12px;color:#a78bfa;"></i>
                    </div>
                    <h5 style="margin:0;font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:2px;color:#fff;">Support</h5>
                </div>
                <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:13px;">
                    <?php foreach ([
                        ['#', 'fas fa-book-open',    'Teacher Guide'],
                        ['#', 'fas fa-question-circle','Help Center'],
                        ['#', 'fas fa-users-cog',    'Community'],
                        ['#', 'fas fa-headset',      'Contact Support'],
                        ['#', 'fas fa-shield-alt',   'Privacy Policy'],
                    ] as [$href, $icon, $label]): ?>
                    <li>
                        <a href="<?php echo $href; ?>"
                           style="display:flex;align-items:center;gap:10px;color:var(--text-muted);font-size:14px;text-decoration:none;transition:all 0.2s;padding:4px 0;"
                           onmouseover="this.style.color='#a78bfa';this.querySelector('i').style.color='#a78bfa'"
                           onmouseout="this.style.color='var(--text-muted)';this.querySelector('i').style.color='var(--text-muted)'">
                            <i class="<?php echo $icon; ?>" style="width:14px;font-size:12px;color:var(--text-muted);transition:color 0.2s;"></i>
                            <?php echo $label; ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Teaching Stats (right panel) -->
            <div style="background:linear-gradient(135deg,#0d1a10,#0a1628);border:1px solid rgba(34,197,94,0.2);border-radius:var(--radius-xl);padding:20px 18px;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:16px;">
                    <i class="fas fa-chart-pie" style="color:var(--primary);font-size:14px;"></i>
                    <span style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:1.5px;color:#fff;">Teaching Stats</span>
                </div>
                <!-- 2x2 stat grid -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                    <?php
                    $statsGrid = [
                        ['fas fa-book',       'Courses',      $totalCourses ?? 0,                                '#22c55e','rgba(34,197,94,0.1)'],
                        ['fas fa-users',      'Students',     $totalStudents ?? 0,                              '#60a5fa','rgba(59,130,246,0.1)'],
                        ['fas fa-star',       'Avg Rating',   isset($footerAvgRating) ? $footerAvgRating : '—', '#fbbf24','rgba(251,191,36,0.1)'],
                        ['fas fa-certificate','Certificates', isset($footerCertCount) ? $footerCertCount : 0,   '#a78bfa','rgba(139,92,246,0.1)'],
                    ];
                    foreach ($statsGrid as [$icon, $label, $val, $color, $bg]): ?>
                    <div style="background:<?php echo $bg; ?>;border:1px solid rgba(255,255,255,0.06);border-radius:10px;padding:10px 8px;text-align:center;">
                        <div style="width:28px;height:28px;background:<?php echo $bg; ?>;border-radius:7px;display:flex;align-items:center;justify-content:center;margin:0 auto 6px;">
                            <i class="<?php echo $icon; ?>" style="color:<?php echo $color; ?>;font-size:12px;"></i>
                        </div>
                        <div style="font-size:17px;font-weight:900;color:<?php echo $color; ?>;line-height:1;margin-bottom:3px;"><?php echo $val; ?></div>
                        <div style="font-size:10px;color:var(--text-muted);font-weight:600;"><?php echo $label; ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>

        <!-- ── Bottom bar ── -->
        <div style="padding:20px 0;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
            <div style="display:flex;align-items:center;gap:10px;">
                <span style="font-size:13px;color:var(--text-muted);">
                    &copy; <?php echo date('Y'); ?> EDUCORE Teacher Portal. All Rights Reserved.
                </span>
            </div>
            <div style="display:flex;gap:24px;align-items:center;">
                <?php foreach (['Privacy','Terms','Cookies'] as $link): ?>
                <a href="#" style="font-size:13px;color:var(--text-muted);text-decoration:none;transition:color 0.2s;"
                   onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-muted)'"><?php echo $link; ?></a>
                <?php endforeach; ?>
                <a href="<?php echo BASE_URL; ?>/Homepage"
                   style="font-size:13px;color:var(--primary);text-decoration:none;display:flex;align-items:center;gap:5px;font-weight:600;"
                   onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">
                    <i class="fas fa-external-link-alt" style="font-size:10px;"></i> Student View
                </a>
            </div>
        </div>

    </div>
</footer>

<?php elseif ($isAdmin): ?>
<!-- ════════════════════════════════════════════
     ADMIN FOOTER
════════════════════════════════════════════ -->
<footer style="background:#060d1a;margin-top:60px;">

    <!-- ── Main footer body ── -->
    <div class="container" style="padding-top:52px;padding-bottom:0;">

        <!-- Row: 3 link columns + system status panel -->
        <div style="display:grid;grid-template-columns:1.3fr 1fr 1fr 1fr 1.3fr;gap:40px;padding-bottom:52px;border-bottom:1px solid rgba(255,255,255,0.07);">

            <!-- ── Brand + description ── -->
            <div>
                <a href="<?php echo BASE_URL; ?>/Homepage"
                   style="display:inline-flex;align-items:center;gap:10px;text-decoration:none;margin-bottom:18px;">
                    <div style="width:40px;height:40px;background:linear-gradient(135deg,#ef4444,#dc2626);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;box-shadow:0 0 16px rgba(239,68,68,0.3);">🛡️</div>
                    <span style="font-family:var(--font-heading);font-size:20px;font-weight:900;color:#fff;letter-spacing:-0.3px;">EDU<span style="color:#ef4444;">CORE</span></span>
                </a>

                <p style="color:var(--text-muted);font-size:14px;line-height:1.75;margin-bottom:22px;max-width:220px;">
                    EDUCORE Admin Panel — manage users, courses, payments, and platform settings from one place.
                </p>

                <!-- Version badge -->
                <div style="display:inline-flex;align-items:center;gap:8px;background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2);border-radius:50px;padding:6px 14px;">
                    <span style="width:7px;height:7px;background:#ef4444;border-radius:50%;display:inline-block;box-shadow:0 0 6px #ef4444;"></span>
                    <span style="font-size:12px;font-weight:700;color:#f87171;letter-spacing:0.5px;">EDUCORE v1.0 — Admin</span>
                </div>
            </div>

            <!-- Quick Links -->
            <div>
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:22px;">
                    <div style="width:28px;height:28px;background:rgba(239,68,68,0.12);border-radius:7px;display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-tachometer-alt" style="font-size:12px;color:#ef4444;"></i>
                    </div>
                    <h5 style="margin:0;font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:2px;color:#fff;">Quick Links</h5>
                </div>
                <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:13px;">
                    <?php foreach ([
                        ['?tab=overview',  'fas fa-chart-bar',          'Dashboard'],
                        ['?tab=users',     'fas fa-users-cog',          'User Management'],
                        ['?tab=courses',   'fas fa-book-open',          'Course Management'],
                        ['?tab=users',     'fas fa-chalkboard-teacher', 'Teacher Management'],
                        ['?tab=users',     'fas fa-user-graduate',      'Student Management'],
                        ['?tab=payments',  'fas fa-chart-line',         'Reports & Analytics'],
                    ] as [$href, $icon, $label]): ?>
                    <li>
                        <a href="<?php echo BASE_URL; ?>/dashboard/admin.php<?php echo $href; ?>"
                           style="display:flex;align-items:center;gap:10px;color:var(--text-muted);font-size:14px;text-decoration:none;transition:all 0.2s;padding:4px 0;"
                           onmouseover="this.style.color='#ef4444';this.querySelector('i').style.color='#ef4444'"
                           onmouseout="this.style.color='var(--text-muted)';this.querySelector('i').style.color='var(--text-muted)'">
                            <i class="<?php echo $icon; ?>" style="width:14px;font-size:12px;color:var(--text-muted);transition:color 0.2s;"></i>
                            <?php echo $label; ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- System -->
            <div>
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:22px;">
                    <div style="width:28px;height:28px;background:rgba(59,130,246,0.12);border-radius:7px;display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-server" style="font-size:12px;color:#60a5fa;"></i>
                    </div>
                    <h5 style="margin:0;font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:2px;color:#fff;">System</h5>
                </div>
                <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:13px;">
                    <?php foreach ([
                        ['#', 'fas fa-sliders-h',      'Site Settings'],
                        ['?tab=payments', 'fas fa-rupee-sign', 'Payment Management'],
                        ['#', 'fas fa-database',        'Backup & Restore'],
                        ['#', 'fas fa-bell',            'Notifications'],
                        ['#', 'fas fa-history',         'Activity Logs'],
                    ] as [$href, $icon, $label]): ?>
                    <li>
                        <a href="<?php echo $href === '#' ? '#' : BASE_URL . '/dashboard/admin.php' . $href; ?>"
                           style="display:flex;align-items:center;gap:10px;color:var(--text-muted);font-size:14px;text-decoration:none;transition:all 0.2s;padding:4px 0;"
                           onmouseover="this.style.color='#60a5fa';this.querySelector('i').style.color='#60a5fa'"
                           onmouseout="this.style.color='var(--text-muted)';this.querySelector('i').style.color='var(--text-muted)'">
                            <i class="<?php echo $icon; ?>" style="width:14px;font-size:12px;color:var(--text-muted);transition:color 0.2s;"></i>
                            <?php echo $label; ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Support -->
            <div>
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:22px;">
                    <div style="width:28px;height:28px;background:rgba(139,92,246,0.12);border-radius:7px;display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-life-ring" style="font-size:12px;color:#a78bfa;"></i>
                    </div>
                    <h5 style="margin:0;font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:2px;color:#fff;">Support</h5>
                </div>
                <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:13px;">
                    <?php foreach ([
                        ['#', 'fas fa-book-open',       'Admin Guide'],
                        ['#', 'fas fa-file-alt',        'Documentation'],
                        ['#', 'fas fa-headset',         'Contact Developer'],
                        ['#', 'fas fa-shield-alt',      'Privacy Policy'],
                        ['#', 'fas fa-file-contract',   'Terms of Service'],
                    ] as [$href, $icon, $label]): ?>
                    <li>
                        <a href="<?php echo $href; ?>"
                           style="display:flex;align-items:center;gap:10px;color:var(--text-muted);font-size:14px;text-decoration:none;transition:all 0.2s;padding:4px 0;"
                           onmouseover="this.style.color='#a78bfa';this.querySelector('i').style.color='#a78bfa'"
                           onmouseout="this.style.color='var(--text-muted)';this.querySelector('i').style.color='var(--text-muted)'">
                            <i class="<?php echo $icon; ?>" style="width:14px;font-size:12px;color:var(--text-muted);transition:color 0.2s;"></i>
                            <?php echo $label; ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- System Status panel -->
            <div style="background:linear-gradient(135deg,#1a0a0a,#0a0d1a);border:1px solid rgba(239,68,68,0.2);border-radius:var(--radius-xl);padding:28px;">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:24px;">
                    <i class="fas fa-signal" style="color:#ef4444;font-size:16px;"></i>
                    <span style="font-size:13px;font-weight:800;text-transform:uppercase;letter-spacing:1.5px;color:#fff;">System Status</span>
                </div>

                <!-- 2x2 stat grid -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px;">
                    <?php
                    $adminStatsGrid = [
                        ['fas fa-users',     'Total Users',   ($adminFooterStats['total_users']  ?? 0), '#60a5fa', 'rgba(59,130,246,0.1)'],
                        ['fas fa-book',      'Total Courses', ($adminFooterStats['total_courses'] ?? 0), '#22c55e', 'rgba(34,197,94,0.1)'],
                    ];
                    foreach ($adminStatsGrid as [$icon, $label, $val, $color, $bg]): ?>
                    <div style="background:<?php echo $bg; ?>;border:1px solid rgba(255,255,255,0.06);border-radius:12px;padding:16px 14px;text-align:center;">
                        <div style="width:36px;height:36px;background:<?php echo $bg; ?>;border-radius:9px;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;">
                            <i class="<?php echo $icon; ?>" style="color:<?php echo $color; ?>;font-size:15px;"></i>
                        </div>
                        <div style="font-size:22px;font-weight:900;color:<?php echo $color; ?>;line-height:1;margin-bottom:4px;"><?php echo $val; ?></div>
                        <div style="font-size:11px;color:var(--text-muted);font-weight:600;"><?php echo $label; ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Server status row -->
                <div style="background:rgba(34,197,94,0.06);border:1px solid rgba(34,197,94,0.15);border-radius:10px;padding:12px 14px;display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span style="width:8px;height:8px;background:#22c55e;border-radius:50%;display:inline-block;box-shadow:0 0 6px #22c55e;animation:pulse 2s infinite;"></span>
                        <span style="font-size:13px;font-weight:600;color:#fff;">Server Status</span>
                    </div>
                    <span style="font-size:12px;font-weight:700;color:#4ade80;">Online</span>
                </div>

                <!-- Version row -->
                <div style="background:rgba(139,92,246,0.06);border:1px solid rgba(139,92,246,0.15);border-radius:10px;padding:12px 14px;display:flex;align-items:center;justify-content:space-between;">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <i class="fas fa-code-branch" style="color:#a78bfa;font-size:13px;"></i>
                        <span style="font-size:13px;font-weight:600;color:#fff;">Version</span>
                    </div>
                    <span style="font-size:12px;font-weight:700;color:#c4b5fd;">EDUCORE v1.0</span>
                </div>
            </div>

        </div>

        <!-- ── Bottom bar ── -->
        <div style="padding:20px 0;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
            <div style="display:flex;align-items:center;gap:10px;">
                <span style="font-size:13px;color:var(--text-muted);">
                    &copy; <?php echo date('Y'); ?> EDUCORE Admin Panel. All Rights Reserved.
                </span>
            </div>
            <div style="display:flex;gap:24px;align-items:center;">
                <?php foreach (['Privacy','Terms','Cookies'] as $link): ?>
                <a href="#" style="font-size:13px;color:var(--text-muted);text-decoration:none;transition:color 0.2s;"
                   onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='var(--text-muted)'"><?php echo $link; ?></a>
                <?php endforeach; ?>
                <a href="<?php echo BASE_URL; ?>/Homepage"
                   style="font-size:13px;color:#ef4444;text-decoration:none;display:flex;align-items:center;gap:5px;font-weight:600;"
                   onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">
                    <i class="fas fa-external-link-alt" style="font-size:10px;"></i> View Public Site
                </a>
            </div>
        </div>

    </div>
</footer>

<style>
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50%       { opacity: 0.4; }
}
</style>

<?php elseif ($isMinimal): ?>
<!-- ════════════════════════════════════════════
     MINIMAL FOOTER (admin, other dashboards)
════════════════════════════════════════════ -->
<footer style="border-top:1px solid var(--border);padding:14px 24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;font-size:12px;color:var(--text-muted);background:var(--bg-darker);margin-top:auto;">
    <span>&copy; <?php echo date('Y'); ?> <a href="<?php echo BASE_URL; ?>/Homepage" style="color:var(--primary);font-weight:600;text-decoration:none;">EDUCORE</a> — All rights reserved.</span>
    <div style="display:flex;align-items:center;gap:16px;">
        <a href="<?php echo BASE_URL; ?>/courses/index.php" style="color:var(--text-muted);" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-muted)'">Browse Courses</a>
        <a href="#" style="color:var(--text-muted);" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-muted)'">Help</a>
        <a href="#" style="color:var(--text-muted);" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-muted)'">Privacy</a>
    </div>
</footer>

<?php else: ?>
<!-- ════════════════════════════════════════════
     FULL FOOTER (public pages)
════════════════════════════════════════════ -->
<footer class="educore-footer">
    <div class="footer-wave">
        <svg viewBox="0 0 1440 80" preserveAspectRatio="none"><path d="M0,40 C360,80 1080,0 1440,40 L1440,0 L0,0 Z" fill="var(--bg-darker)"/></svg>
    </div>
    <div class="container">
        <div class="row g-4 footer-main">
            <!-- Brand -->
            <div class="col-lg-4">
                <a href="<?php echo BASE_URL; ?>/Homepage" class="footer-brand">
                    <div class="logo-icon-sm"><i class="fas fa-graduation-cap"></i></div>
                    <span>EDU<span class="logo-accent">CORE</span></span>
                </a>
                <p class="footer-tagline">Empowering learners worldwide with quality education. Build skills that matter, at your own pace.</p>
                <div class="social-links">
                    <a href="#" class="social-btn" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-btn" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="social-btn" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-btn" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#" class="social-btn" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            <!-- Courses -->
            <div class="col-6 col-md-3 col-lg-2">
                <h5 class="footer-heading">Courses</h5>
                <ul class="footer-links">
                    <li><a href="<?php echo BASE_URL; ?>/courses/index.php?category=programming">Programming</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/courses/index.php?category=design">Design</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/courses/index.php?category=business">Business</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/courses/index.php?category=data-science">Data Science</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/courses/index.php?type=free">Free Courses</a></li>
                </ul>
            </div>
            <!-- Company -->
            <div class="col-6 col-md-3 col-lg-2">
                <h5 class="footer-heading">Company</h5>
                <ul class="footer-links">
                    <li><a href="#">About Us</a></li>
                    <li><a href="#">Careers</a></li>
                    <li><a href="#">Blog</a></li>
                    <li><a href="#">Press</a></li>
                    <li><a href="#">Affiliates</a></li>
                </ul>
            </div>
            <!-- Support -->
            <div class="col-6 col-md-3 col-lg-2">
                <h5 class="footer-heading">Support</h5>
                <ul class="footer-links">
                    <li><a href="#">Help Center</a></li>
                    <li><a href="#">Contact Us</a></li>
                    <li><a href="#">Community</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Terms of Use</a></li>
                </ul>
            </div>
            <!-- Newsletter -->
            <div class="col-lg-2">
                <h5 class="footer-heading">Stay Updated</h5>
                <p class="footer-desc">Get new course alerts and tips delivered to your inbox.</p>
                <form class="newsletter-form" onsubmit="subscribeNewsletter(event)">
                    <input type="email" placeholder="Your email" class="newsletter-input" required>
                    <button type="submit" class="newsletter-btn"><i class="fas fa-paper-plane"></i></button>
                </form>
                <div class="footer-badges mt-3">
                    <div class="stat-badge"><i class="fas fa-users"></i><span>50K+<br><small>Students</small></span></div>
                    <div class="stat-badge"><i class="fas fa-book-open"></i><span>200+<br><small>Courses</small></span></div>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <span>&copy; <?php echo date('Y'); ?> EDUCORE. All rights reserved. Built with <i class="fas fa-heart text-danger"></i> for learners everywhere.</span>
            <div class="footer-bottom-links">
                <a href="#">Privacy</a>
                <a href="#">Terms</a>
                <a href="#">Cookies</a>
            </div>
        </div>
    </div>
</footer>
<?php endif; ?>

<!-- ============ TOAST NOTIFICATION ============ -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:9999;" id="toastContainer"></div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>

<script>
// Load notifications on dropdown open
document.getElementById('notifBtn')?.addEventListener('click', loadNotifications);

function loadNotifications() {
    fetch('<?php echo BASE_URL; ?>/api/notifications.php')
        .then(r => r.json())
        .then(data => {
            const list = document.getElementById('notifList');
            if (!list) return;
            if (!data.length) {
                list.innerHTML = '<div class="notif-empty"><i class="fas fa-bell-slash"></i><p>No notifications</p></div>';
                return;
            }
            list.innerHTML = data.map(n => `
                <div class="notif-item ${n.is_read ? '' : 'unread'}" style="color:#fff;">
                    <div class="notif-icon type-${n.type}"><i class="fas fa-${n.type === 'success' ? 'check' : 'info-circle'}"></i></div>
                    <div class="notif-content">
                        <p style="color:#fff;margin:0;font-size:13px;line-height:1.5;">${n.message}</p>
                        <small style="color:#9ca3af;font-size:11px;">${n.time_ago}</small>
                    </div>
                </div>
            `).join('');
        }).catch(() => {});
}

function markAllRead() {
    fetch('<?php echo BASE_URL; ?>/api/notifications.php?action=mark_read', {method:'POST'})
        .then(() => {
            document.querySelectorAll('.notif-badge').forEach(el => el.remove());
            document.querySelectorAll('.notif-item.unread').forEach(el => el.classList.remove('unread'));
        });
}

function subscribeNewsletter(e) {
    e.preventDefault();
    showToast('success', 'Subscribed! You\'ll receive updates soon. 🎉');
    e.target.reset();
}

function toggleMobileMenu() {
    document.getElementById('mobileMenu').classList.toggle('open');
}

// Scroll-based navbar effect
window.addEventListener('scroll', () => {
    const nav = document.getElementById('mainNavbar');
    if (nav) nav.classList.toggle('scrolled', window.scrollY > 50);
});
</script>

<?php if (isset($extraScripts)) echo $extraScripts; ?>
</body>
</html>
