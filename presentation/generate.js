import pptxgen from 'pptxgenjs';

const COLORS = {
    indigo900: '312e81',
    indigo800: '3730a3',
    indigo600: '4f46e5',
    indigo500: '6366f1',
    indigo400: '818cf8',
    indigo200: 'c7d2fe',
    indigo100: 'e0e7ff',
    indigo50: 'eef2ff',
    white: 'FFFFFF',
    gray50: 'f9fafb',
    gray100: 'f3f4f6',
    gray400: '9ca3af',
    gray600: '4b5563',
    gray700: '374151',
    green600: '16a34a',
    green100: 'dcfce7',
    amber500: 'f59e0b',
    amber100: 'fef3c7',
    red600: 'dc2626',
    red100: 'fee2e2',
    blue500: '3b82f6',
    blue100: 'dbeafe',
};

const FONTS = {
    heading: 'Segoe UI',
    body: 'Segoe UI',
};

const pptx = new pptxgen();
pptx.layout = 'LAYOUT_WIDE';
pptx.author = 'Evalium';
pptx.company = 'Evalium';
pptx.subject = 'Evalium - Platform Presentation';
pptx.title = 'Evalium - Where every grade tells a story';

/**
 * Adds the standard slide header bar with title.
 *
 * @param {object} slide
 * @param {string} title
 */
function addHeader(slide, title) {
    slide.addShape(pptx.ShapeType.rect, {
        x: 0,
        y: 0,
        w: '100%',
        h: 1.1,
        fill: { color: COLORS.indigo600 },
    });

    slide.addShape(pptx.ShapeType.rect, {
        x: 0,
        y: 1.0,
        w: '100%',
        h: 0.06,
        fill: { color: COLORS.indigo400 },
    });

    slide.addText(title, {
        x: 0.4,
        y: 0.0,
        w: 9.5,
        h: 1.05,
        fontSize: 26,
        bold: true,
        color: COLORS.white,
        fontFace: FONTS.heading,
        valign: 'middle',
    });
}

/**
 * Adds the standard slide footer with page number and brand.
 *
 * @param {object} slide
 * @param {number} pageNum
 */
function addFooter(slide, pageNum) {
    slide.addShape(pptx.ShapeType.rect, {
        x: 0,
        y: 7.1,
        w: '100%',
        h: 0.4,
        fill: { color: COLORS.indigo900 },
    });

    slide.addText('Evalium  |  Where every grade tells a story', {
        x: 0.3,
        y: 7.1,
        w: 8,
        h: 0.4,
        fontSize: 9,
        color: COLORS.indigo200,
        fontFace: FONTS.body,
        valign: 'middle',
    });

    slide.addText(`${pageNum}`, {
        x: 12.2,
        y: 7.1,
        w: 0.9,
        h: 0.4,
        fontSize: 9,
        color: COLORS.indigo200,
        fontFace: FONTS.body,
        valign: 'middle',
        align: 'right',
    });
}

/**
 * Draws a minimal gem prism icon using shapes (top + left + right faces).
 *
 * @param {object} slide
 * @param {number} x
 * @param {number} y
 * @param {number} size - base unit in inches
 */
function addGem(slide, x, y, size = 1) {
    const s = size;
    slide.addShape(pptx.ShapeType.triangle, {
        x: x,
        y: y,
        w: s * 2,
        h: s,
        fill: { color: COLORS.indigo400 },
        line: { color: COLORS.white, width: 1 },
    });
    slide.addShape(pptx.ShapeType.triangle, {
        x: x,
        y: y + s * 0.85,
        w: s,
        h: s * 1.4,
        fill: { color: COLORS.indigo600 },
        line: { color: COLORS.white, width: 1 },
    });
    slide.addShape(pptx.ShapeType.triangle, {
        x: x + s,
        y: y + s * 0.85,
        w: s,
        h: s * 1.4,
        fill: { color: COLORS.indigo500 },
        line: { color: COLORS.white, width: 1 },
    });
}

// ─────────────────────────────────────────────
// SLIDE 1 — Cover
// ─────────────────────────────────────────────
{
    const slide = pptx.addSlide();

    slide.addShape(pptx.ShapeType.rect, {
        x: 0,
        y: 0,
        w: '100%',
        h: '100%',
        fill: { color: COLORS.indigo900 },
    });

    slide.addShape(pptx.ShapeType.rect, {
        x: 0,
        y: 5.6,
        w: '100%',
        h: 1.9,
        fill: { color: COLORS.indigo800 },
    });

    slide.addShape(pptx.ShapeType.rect, {
        x: 0,
        y: 5.55,
        w: '100%',
        h: 0.08,
        fill: { color: COLORS.indigo400 },
    });

    addGem(slide, 5.2, 0.8, 0.9);

    slide.addText('Evalium', {
        x: 0,
        y: 2.5,
        w: '100%',
        h: 1.2,
        fontSize: 72,
        bold: true,
        color: COLORS.white,
        fontFace: FONTS.heading,
        align: 'center',
        valign: 'middle',
    });

    slide.addText('Where every grade tells a story', {
        x: 0,
        y: 3.65,
        w: '100%',
        h: 0.55,
        fontSize: 18,
        italic: true,
        color: COLORS.indigo200,
        fontFace: FONTS.body,
        align: 'center',
    });

    slide.addShape(pptx.ShapeType.line, {
        x: 4.2,
        y: 4.35,
        w: 4.8,
        h: 0,
        line: { color: COLORS.indigo500, width: 1.5 },
    });

    slide.addText('Online Assessment Management Platform', {
        x: 0,
        y: 4.5,
        w: '100%',
        h: 0.45,
        fontSize: 13,
        color: COLORS.indigo300 ?? COLORS.indigo400,
        fontFace: FONTS.body,
        align: 'center',
    });

    slide.addText('February 2026', {
        x: 0,
        y: 5.75,
        w: '100%',
        h: 0.4,
        fontSize: 11,
        color: COLORS.indigo200,
        fontFace: FONTS.body,
        align: 'center',
    });
}

// ─────────────────────────────────────────────
// SLIDE 2 — Agenda
// ─────────────────────────────────────────────
{
    const slide = pptx.addSlide();
    slide.addShape(pptx.ShapeType.rect, {
        x: 0,
        y: 0,
        w: '100%',
        h: '100%',
        fill: { color: COLORS.gray50 },
    });

    addHeader(slide, 'Agenda');
    addFooter(slide, 2);

    const items = [
        ['01', 'Platform Overview', 'What Evalium is and what problem it solves'],
        ['02', 'Technology Stack', 'Laravel 12, React 18, Inertia.js v2, TypeScript'],
        ['03', 'User Roles & Functionalities', 'Admin · Teacher · Student'],
        ['04', 'Academic Structure', 'Years → Semesters → Classes → Subjects'],
        ['05', 'Assessment Lifecycle', 'Creation → Publishing → Taking → Grading'],
        ['06', 'Database Schema', 'Domain groups, key tables, relationships'],
        ['07', 'Security & Authorization', 'Exam security, hybrid permission model'],
        ['08', 'Key Design Decisions', 'Architecture choices and trade-offs'],
    ];

    items.forEach(([num, title, desc], i) => {
        const x = i < 4 ? 0.4 : 6.6;
        const y = 1.3 + (i % 4) * 1.35;

        slide.addShape(pptx.ShapeType.rect, {
            x,
            y,
            w: 5.8,
            h: 1.15,
            fill: { color: COLORS.white },
            line: { color: COLORS.indigo200, width: 1 },
            shadow: { type: 'outer', color: 'cccccc', blur: 4, offset: 2, angle: 270 },
        });

        slide.addShape(pptx.ShapeType.rect, {
            x,
            y,
            w: 0.55,
            h: 1.15,
            fill: { color: COLORS.indigo600 },
        });

        slide.addText(num, {
            x,
            y,
            w: 0.55,
            h: 1.15,
            fontSize: 14,
            bold: true,
            color: COLORS.white,
            fontFace: FONTS.heading,
            align: 'center',
            valign: 'middle',
        });

        slide.addText(title, {
            x: x + 0.65,
            y: y + 0.1,
            w: 5.05,
            h: 0.42,
            fontSize: 12,
            bold: true,
            color: COLORS.indigo900,
            fontFace: FONTS.heading,
            valign: 'middle',
        });

        slide.addText(desc, {
            x: x + 0.65,
            y: y + 0.52,
            w: 5.05,
            h: 0.5,
            fontSize: 9.5,
            color: COLORS.gray600,
            fontFace: FONTS.body,
            valign: 'top',
        });
    });
}

// ─────────────────────────────────────────────
// SLIDE 3 — Platform Overview
// ─────────────────────────────────────────────
{
    const slide = pptx.addSlide();
    slide.addShape(pptx.ShapeType.rect, {
        x: 0,
        y: 0,
        w: '100%',
        h: '100%',
        fill: { color: COLORS.white },
    });

    addHeader(slide, 'Platform Overview');
    addFooter(slide, 3);

    slide.addText('What is Evalium?', {
        x: 0.4,
        y: 1.25,
        w: 12.5,
        h: 0.5,
        fontSize: 18,
        bold: true,
        color: COLORS.indigo900,
        fontFace: FONTS.heading,
    });

    slide.addText(
        'Evalium is a modern, web-based assessment management platform designed for educational institutions. ' +
            'It centralizes the full lifecycle of academic evaluations—from creation to grading—within a single, ' +
            'secure, multi-role application.',
        {
            x: 0.4,
            y: 1.8,
            w: 12.5,
            h: 0.85,
            fontSize: 12,
            color: COLORS.gray700,
            fontFace: FONTS.body,
        },
    );

    const pillars = [
        {
            icon: '📋',
            title: 'Assessment Creation',
            desc: 'Teachers build rich assessments with 5 question types, coefficients, and scheduling options.',
            color: COLORS.indigo100,
            border: COLORS.indigo400,
        },
        {
            icon: '🎓',
            title: 'Student Experience',
            desc: 'Students take supervised or homework assessments in a secure, monitored environment.',
            color: COLORS.green100,
            border: COLORS.green600,
        },
        {
            icon: '✅',
            title: 'Grading & Feedback',
            desc: 'Teachers correct answers per question with detailed scores and written feedback.',
            color: COLORS.amber100,
            border: COLORS.amber500,
        },
        {
            icon: '📊',
            title: 'Analytics & Stats',
            desc: 'Real-time statistics per assessment, per class, and per student to track progress.',
            color: COLORS.blue100,
            border: COLORS.blue500,
        },
    ];

    pillars.forEach((p, i) => {
        const x = 0.3 + i * 3.2;
        slide.addShape(pptx.ShapeType.rect, {
            x,
            y: 2.85,
            w: 3.0,
            h: 3.85,
            fill: { color: p.color },
            line: { color: p.border, width: 1.5 },
        });

        slide.addText(p.icon, {
            x,
            y: 3.0,
            w: 3.0,
            h: 0.7,
            fontSize: 28,
            align: 'center',
        });

        slide.addText(p.title, {
            x,
            y: 3.75,
            w: 3.0,
            h: 0.55,
            fontSize: 11.5,
            bold: true,
            color: COLORS.indigo900,
            fontFace: FONTS.heading,
            align: 'center',
        });

        slide.addText(p.desc, {
            x: x + 0.15,
            y: 4.35,
            w: 2.7,
            h: 2.1,
            fontSize: 10,
            color: COLORS.gray700,
            fontFace: FONTS.body,
            align: 'center',
        });
    });
}

// ─────────────────────────────────────────────
// SLIDE 4 — Technology Stack
// ─────────────────────────────────────────────
{
    const slide = pptx.addSlide();
    slide.addShape(pptx.ShapeType.rect, {
        x: 0,
        y: 0,
        w: '100%',
        h: '100%',
        fill: { color: COLORS.gray50 },
    });

    addHeader(slide, 'Technology Stack');
    addFooter(slide, 4);

    const techs = [
        {
            layer: 'Backend',
            items: [
                'Laravel 12 (PHP 8.3+)',
                'Eloquent ORM',
                'Spatie Permissions',
                'Laravel Queue (Jobs)',
                'PHPUnit 11 (Tests)',
            ],
        },
        {
            layer: 'Frontend',
            items: [
                'React 18 + TypeScript',
                'Inertia.js v2',
                'Tailwind CSS',
                'Ziggy (named routes)',
                'laravel-react-i18n (i18n)',
            ],
        },
        {
            layer: 'Architecture',
            items: [
                'Service-Oriented (SRP)',
                'Strategy Pattern (Validation)',
                'Policy-based Authorization',
                'Form Request Validation',
                'API Resources (Transformation)',
            ],
        },
        {
            layer: 'Testing & CI',
            items: [
                'PHPUnit (Backend)',
                'Vitest / Jest (Frontend)',
                'Playwright (E2E)',
                'GitHub Actions CI/CD',
                'Min 70% coverage enforced',
            ],
        },
    ];

    techs.forEach((col, i) => {
        const x = 0.3 + i * 3.2;
        slide.addShape(pptx.ShapeType.rect, {
            x,
            y: 1.25,
            w: 3.05,
            h: 0.52,
            fill: { color: COLORS.indigo600 },
        });
        slide.addText(col.layer, {
            x,
            y: 1.25,
            w: 3.05,
            h: 0.52,
            fontSize: 12,
            bold: true,
            color: COLORS.white,
            fontFace: FONTS.heading,
            align: 'center',
            valign: 'middle',
        });

        slide.addShape(pptx.ShapeType.rect, {
            x,
            y: 1.77,
            w: 3.05,
            h: 5.1,
            fill: { color: COLORS.white },
            line: { color: COLORS.indigo200, width: 1 },
        });

        col.items.forEach((item, j) => {
            slide.addShape(pptx.ShapeType.rect, {
                x: x + 0.12,
                y: 1.95 + j * 0.92,
                w: 2.82,
                h: 0.78,
                fill: { color: j % 2 === 0 ? COLORS.indigo50 : COLORS.white },
                line: { color: COLORS.indigo200, width: 0.5 },
            });
            slide.addText(item, {
                x: x + 0.22,
                y: 1.95 + j * 0.92,
                w: 2.62,
                h: 0.78,
                fontSize: 10.5,
                color: COLORS.indigo900,
                fontFace: FONTS.body,
                valign: 'middle',
            });
        });
    });
}

// ─────────────────────────────────────────────
// SLIDE 5 — User Roles Overview
// ─────────────────────────────────────────────
{
    const slide = pptx.addSlide();
    slide.addShape(pptx.ShapeType.rect, {
        x: 0,
        y: 0,
        w: '100%',
        h: '100%',
        fill: { color: COLORS.white },
    });

    addHeader(slide, 'User Roles — Overview');
    addFooter(slide, 5);

    const roles = [
        {
            role: 'Administrator',
            icon: '🛡️',
            color: COLORS.indigo600,
            bg: COLORS.indigo50,
            border: COLORS.indigo400,
            responsibilities: [
                'Manage academic years & semesters',
                'Create & manage levels and subjects',
                'Create & manage classes',
                'Enroll students into classes',
                'Assign teachers to class-subjects',
                'Manage users (roles & activation)',
                'Full platform configuration',
            ],
        },
        {
            role: 'Teacher',
            icon: '👩‍🏫',
            color: COLORS.green600,
            bg: COLORS.green100,
            border: COLORS.green600,
            responsibilities: [
                'Create assessments for assigned classes',
                'Add questions (5 types) with points',
                'Publish / schedule assessments',
                'Monitor assessment progress',
                'Grade submitted answers',
                'Write per-question feedback',
                'View class & student statistics',
            ],
        },
        {
            role: 'Student',
            icon: '🎓',
            color: COLORS.amber500,
            bg: COLORS.amber100,
            border: COLORS.amber500,
            responsibilities: [
                'Access published assessments',
                'Start supervised or homework sessions',
                'Answer all question types',
                'Upload file answers',
                'View own results & feedback',
                'Track personal progress',
                'Access via class enrollment only',
            ],
        },
    ];

    roles.forEach((r, i) => {
        const x = 0.35 + i * 4.3;

        slide.addShape(pptx.ShapeType.rect, {
            x,
            y: 1.25,
            w: 4.1,
            h: 5.65,
            fill: { color: r.bg },
            line: { color: r.border, width: 2 },
            shadow: { type: 'outer', color: 'cccccc', blur: 6, offset: 2, angle: 270 },
        });

        slide.addShape(pptx.ShapeType.rect, {
            x,
            y: 1.25,
            w: 4.1,
            h: 0.95,
            fill: { color: r.color },
        });

        slide.addText(r.icon + '  ' + r.role, {
            x,
            y: 1.25,
            w: 4.1,
            h: 0.95,
            fontSize: 15,
            bold: true,
            color: COLORS.white,
            fontFace: FONTS.heading,
            align: 'center',
            valign: 'middle',
        });

        r.responsibilities.forEach((item, j) => {
            slide.addShape(pptx.ShapeType.rect, {
                x: x + 0.15,
                y: 2.3 + j * 0.62,
                w: 0.22,
                h: 0.22,
                fill: { color: r.color },
            });

            slide.addText(item, {
                x: x + 0.45,
                y: 2.26 + j * 0.62,
                w: 3.55,
                h: 0.42,
                fontSize: 10,
                color: COLORS.gray700,
                fontFace: FONTS.body,
                valign: 'middle',
            });
        });
    });
}

// ─────────────────────────────────────────────
// SLIDE 6 — Academic Structure
// ─────────────────────────────────────────────
{
    const slide = pptx.addSlide();
    slide.addShape(pptx.ShapeType.rect, {
        x: 0,
        y: 0,
        w: '100%',
        h: '100%',
        fill: { color: COLORS.gray50 },
    });

    addHeader(slide, 'Academic Structure');
    addFooter(slide, 6);

    const levels = [
        {
            label: 'Academic Year',
            desc: 'Top-level context. One year is marked as current.\nAll data is scoped per year.',
            color: COLORS.indigo600,
            x: 0.4,
            y: 1.3,
            w: 12.3,
            h: 0.9,
        },
        {
            label: 'Semester',
            desc: 'Up to 2 semesters per year (ordered 1 → 2).\nOptionally linked to class-subjects.',
            color: COLORS.indigo500,
            x: 0.8,
            y: 2.45,
            w: 11.5,
            h: 0.85,
        },
        {
            label: 'Level',
            desc: 'Educational level (e.g. Terminale, Grade 10). Independent of academic year.',
            color: COLORS.indigo400,
            x: 1.2,
            y: 3.5,
            w: 10.7,
            h: 0.85,
        },
        {
            label: 'Class',
            desc: 'A class belongs to one academic year + one level. Unique name per (year, level).',
            color: COLORS.blue500,
            x: 1.6,
            y: 4.55,
            w: 9.9,
            h: 0.85,
        },
        {
            label: 'Class-Subject',
            desc: 'A subject assigned to a class with a teacher, coefficient, and optional semester scope.',
            color: COLORS.green600,
            x: 2.0,
            y: 5.6,
            w: 9.1,
            h: 0.85,
        },
    ];

    levels.forEach((l) => {
        slide.addShape(pptx.ShapeType.roundRect, {
            x: l.x,
            y: l.y,
            w: l.w,
            h: l.h,
            fill: { color: l.color },
            rectRadius: 0.08,
        });

        slide.addText(l.label, {
            x: l.x + 0.25,
            y: l.y,
            w: 2.8,
            h: l.h,
            fontSize: 12,
            bold: true,
            color: COLORS.white,
            fontFace: FONTS.heading,
            valign: 'middle',
        });

        slide.addText(l.desc, {
            x: l.x + 3.2,
            y: l.y,
            w: l.w - 3.4,
            h: l.h,
            fontSize: 10,
            color: COLORS.white,
            fontFace: FONTS.body,
            valign: 'middle',
        });
    });

    slide.addShape(pptx.ShapeType.line, {
        x: 0.55,
        y: 2.2,
        w: 0,
        h: 0.25,
        line: { color: COLORS.indigo600, width: 1.5 },
    });
    slide.addShape(pptx.ShapeType.line, {
        x: 0.95,
        y: 3.3,
        w: 0,
        h: 0.2,
        line: { color: COLORS.indigo500, width: 1.5 },
    });
    slide.addShape(pptx.ShapeType.line, {
        x: 1.35,
        y: 4.35,
        w: 0,
        h: 0.2,
        line: { color: COLORS.indigo400, width: 1.5 },
    });
    slide.addShape(pptx.ShapeType.line, {
        x: 1.75,
        y: 5.4,
        w: 0,
        h: 0.2,
        line: { color: COLORS.blue500, width: 1.5 },
    });
}

// ─────────────────────────────────────────────
// SLIDE 7 — Assessment Lifecycle
// ─────────────────────────────────────────────
{
    const slide = pptx.addSlide();
    slide.addShape(pptx.ShapeType.rect, {
        x: 0,
        y: 0,
        w: '100%',
        h: '100%',
        fill: { color: COLORS.white },
    });

    addHeader(slide, 'Assessment Lifecycle');
    addFooter(slide, 7);

    const steps = [
        {
            num: '1',
            title: 'Create',
            desc: 'Teacher creates an assessment linked to a class-subject: type, delivery mode, coefficient, duration.',
            color: COLORS.indigo600,
        },
        {
            num: '2',
            title: 'Add Questions',
            desc: 'Add questions (text, multiple, one_choice, boolean, file) with points and order.',
            color: COLORS.indigo500,
        },
        {
            num: '3',
            title: 'Publish',
            desc: 'Mark assessment as published. Enrolled students can see and start it based on schedule or due date.',
            color: COLORS.blue500,
        },
        {
            num: '4',
            title: 'Take',
            desc: 'Students start the assessment. Supervised sessions are timed and monitored for security violations.',
            color: COLORS.amber500,
        },
        {
            num: '5',
            title: 'Submit',
            desc: 'Student submits (manual or forced on timeout). Answers are locked. Assignment is marked submitted.',
            color: COLORS.red600,
        },
        {
            num: '6',
            title: 'Grade',
            desc: 'Teacher reviews each answer, assigns a score and optional feedback. Assignment is marked graded.',
            color: COLORS.green600,
        },
    ];

    steps.forEach((step, i) => {
        const col = i % 3;
        const row = Math.floor(i / 3);
        const x = 0.35 + col * 4.3;
        const y = 1.3 + row * 2.85;

        slide.addShape(pptx.ShapeType.rect, {
            x,
            y,
            w: 4.1,
            h: 2.55,
            fill: { color: COLORS.white },
            line: { color: step.color, width: 2 },
            shadow: { type: 'outer', color: 'dddddd', blur: 5, offset: 2, angle: 270 },
        });

        slide.addShape(pptx.ShapeType.rect, {
            x,
            y,
            w: 4.1,
            h: 0.55,
            fill: { color: step.color },
        });

        slide.addText(`${step.num}. ${step.title}`, {
            x,
            y,
            w: 4.1,
            h: 0.55,
            fontSize: 13,
            bold: true,
            color: COLORS.white,
            fontFace: FONTS.heading,
            align: 'center',
            valign: 'middle',
        });

        slide.addText(step.desc, {
            x: x + 0.2,
            y: y + 0.65,
            w: 3.7,
            h: 1.75,
            fontSize: 10.5,
            color: COLORS.gray700,
            fontFace: FONTS.body,
        });

        if (col < 2) {
            slide.addShape(pptx.ShapeType.line, {
                x: x + 4.1,
                y: y + 1.27,
                w: 0.2,
                h: 0,
                line: { color: COLORS.gray400, width: 1.5 },
            });
        }
    });
}

// ─────────────────────────────────────────────
// SLIDE 8 — Question Types
// ─────────────────────────────────────────────
{
    const slide = pptx.addSlide();
    slide.addShape(pptx.ShapeType.rect, {
        x: 0,
        y: 0,
        w: '100%',
        h: '100%',
        fill: { color: COLORS.gray50 },
    });

    addHeader(slide, 'Question Types');
    addFooter(slide, 8);

    const types = [
        {
            type: 'text',
            icon: '📝',
            title: 'Open Text',
            desc: 'Free-form written answer. Graded manually by teacher with score + feedback.',
            grading: 'Manual',
            gradingColor: COLORS.amber500,
        },
        {
            type: 'multiple',
            icon: '☑️',
            title: 'Multiple Choice',
            desc: 'Several correct answers possible. Student selects all that apply. Requires ≥ 2 correct choices.',
            grading: 'Auto',
            gradingColor: COLORS.green600,
        },
        {
            type: 'one_choice',
            icon: '🔘',
            title: 'Single Choice',
            desc: 'Exactly one correct answer from a list. Classic radio-button question.',
            grading: 'Auto',
            gradingColor: COLORS.green600,
        },
        {
            type: 'boolean',
            icon: '✓✗',
            title: 'True / False',
            desc: 'Binary answer. Student picks True or False. Simple and fast to grade.',
            grading: 'Auto',
            gradingColor: COLORS.green600,
        },
        {
            type: 'file',
            icon: '📎',
            title: 'File Upload',
            desc: 'Student uploads a document or image as answer. File metadata stored in DB.',
            grading: 'Manual',
            gradingColor: COLORS.amber500,
        },
    ];

    types.forEach((t, i) => {
        const x = 0.35 + i * 2.6;

        slide.addShape(pptx.ShapeType.rect, {
            x,
            y: 1.3,
            w: 2.45,
            h: 5.55,
            fill: { color: COLORS.white },
            line: { color: COLORS.indigo200, width: 1 },
            shadow: { type: 'outer', color: 'dddddd', blur: 4, offset: 2, angle: 270 },
        });

        slide.addShape(pptx.ShapeType.rect, {
            x,
            y: 1.3,
            w: 2.45,
            h: 0.6,
            fill: { color: COLORS.indigo600 },
        });

        slide.addText(t.type, {
            x,
            y: 1.3,
            w: 2.45,
            h: 0.6,
            fontSize: 10,
            bold: true,
            color: COLORS.white,
            fontFace: FONTS.heading,
            align: 'center',
            valign: 'middle',
        });

        slide.addText(t.icon, {
            x,
            y: 2.05,
            w: 2.45,
            h: 0.75,
            fontSize: 30,
            align: 'center',
        });

        slide.addText(t.title, {
            x,
            y: 2.9,
            w: 2.45,
            h: 0.45,
            fontSize: 11,
            bold: true,
            color: COLORS.indigo900,
            fontFace: FONTS.heading,
            align: 'center',
        });

        slide.addText(t.desc, {
            x: x + 0.12,
            y: 3.45,
            w: 2.22,
            h: 2.2,
            fontSize: 9.5,
            color: COLORS.gray600,
            fontFace: FONTS.body,
            align: 'center',
        });

        slide.addShape(pptx.ShapeType.rect, {
            x: x + 0.35,
            y: 5.72,
            w: 1.75,
            h: 0.38,
            fill: { color: t.gradingColor },
        });

        slide.addText(t.grading + ' grading', {
            x: x + 0.35,
            y: 5.72,
            w: 1.75,
            h: 0.38,
            fontSize: 9,
            bold: true,
            color: COLORS.white,
            fontFace: FONTS.body,
            align: 'center',
            valign: 'middle',
        });
    });
}

// ─────────────────────────────────────────────
// SLIDE 9 — Database Schema Overview
// ─────────────────────────────────────────────
{
    const slide = pptx.addSlide();
    slide.addShape(pptx.ShapeType.rect, {
        x: 0,
        y: 0,
        w: '100%',
        h: '100%',
        fill: { color: COLORS.white },
    });

    addHeader(slide, 'Database Schema — Domain Overview');
    addFooter(slide, 9);

    const domains = [
        {
            domain: 'Identity & Auth',
            color: COLORS.indigo600,
            bg: COLORS.indigo50,
            tables: [
                'users',
                'roles',
                'permissions',
                'model_has_roles',
                'model_has_permissions',
                'role_has_permissions',
            ],
        },
        {
            domain: 'Academic Structure',
            color: COLORS.blue500,
            bg: COLORS.blue100,
            tables: ['levels', 'academic_years', 'semesters', 'subjects', 'classes'],
        },
        {
            domain: 'Enrollment',
            color: COLORS.green600,
            bg: COLORS.green100,
            tables: ['enrollments', 'class_subjects'],
        },
        {
            domain: 'Assessment',
            color: COLORS.amber500,
            bg: COLORS.amber100,
            tables: ['assessments', 'assessment_assignments', 'questions', 'choices', 'answers'],
        },
        {
            domain: 'System / Infrastructure',
            color: COLORS.gray600,
            bg: COLORS.gray100,
            tables: ['notifications', 'sessions', 'cache', 'jobs', 'password_reset_tokens'],
        },
    ];

    domains.forEach((d, i) => {
        const col = i < 2 ? 0 : i < 4 ? 1 : 2;
        const row = i < 2 ? i : i < 4 ? i - 2 : 0;
        const x = 0.35 + col * 4.3;
        const y = 1.3 + row * 2.95;

        slide.addShape(pptx.ShapeType.rect, {
            x: i === 4 ? 8.95 : x,
            y: i === 4 ? 1.3 : y,
            w: i === 4 ? 4.1 : 4.1,
            h: i === 4 ? 5.55 : 2.65,
            fill: { color: d.bg },
            line: { color: d.color, width: 1.5 },
        });

        slide.addShape(pptx.ShapeType.rect, {
            x: i === 4 ? 8.95 : x,
            y: i === 4 ? 1.3 : y,
            w: i === 4 ? 4.1 : 4.1,
            h: 0.5,
            fill: { color: d.color },
        });

        slide.addText(d.domain, {
            x: i === 4 ? 8.95 : x,
            y: i === 4 ? 1.3 : y,
            w: i === 4 ? 4.1 : 4.1,
            h: 0.5,
            fontSize: 11,
            bold: true,
            color: COLORS.white,
            fontFace: FONTS.heading,
            align: 'center',
            valign: 'middle',
        });

        d.tables.forEach((tbl, j) => {
            slide.addShape(pptx.ShapeType.rect, {
                x: (i === 4 ? 8.95 : x) + 0.15,
                y: (i === 4 ? 1.3 : y) + 0.6 + j * 0.74,
                w: 3.8,
                h: 0.55,
                fill: j % 2 === 0 ? { color: COLORS.white } : { type: 'none' },
                line: { color: d.color, width: 0.3 },
            });

            slide.addText(`  ${tbl}`, {
                x: (i === 4 ? 8.95 : x) + 0.15,
                y: (i === 4 ? 1.3 : y) + 0.6 + j * 0.74,
                w: 3.8,
                h: 0.55,
                fontSize: 10,
                color: COLORS.gray700,
                fontFace: 'Courier New',
                valign: 'middle',
            });
        });
    });
}

// ─────────────────────────────────────────────
// SLIDE 10 — Key DB Tables & Relationships
// ─────────────────────────────────────────────
{
    const slide = pptx.addSlide();
    slide.addShape(pptx.ShapeType.rect, {
        x: 0,
        y: 0,
        w: '100%',
        h: '100%',
        fill: { color: COLORS.gray50 },
    });

    addHeader(slide, 'Database — Key Relationships');
    addFooter(slide, 10);

    const tableBoxes = [
        { name: 'academic_years', x: 0.3, y: 1.4, w: 2.8, color: COLORS.indigo600 },
        { name: 'semesters', x: 0.3, y: 3.0, w: 2.8, color: COLORS.indigo500 },
        { name: 'levels', x: 0.3, y: 4.6, w: 2.8, color: COLORS.indigo400 },
        { name: 'classes', x: 3.6, y: 1.4, w: 2.8, color: COLORS.blue500 },
        { name: 'enrollments', x: 3.6, y: 3.0, w: 2.8, color: COLORS.green600 },
        { name: 'class_subjects', x: 3.6, y: 4.6, w: 2.8, color: COLORS.green600 },
        { name: 'assessments', x: 6.9, y: 1.4, w: 2.8, color: COLORS.amber500 },
        { name: 'assessment_assignments', x: 6.9, y: 3.0, w: 2.8, color: COLORS.amber500 },
        { name: 'questions', x: 10.2, y: 1.4, w: 2.8, color: COLORS.red600 },
        { name: 'answers', x: 10.2, y: 3.0, w: 2.8, color: COLORS.red600 },
        { name: 'users', x: 6.9, y: 5.5, w: 2.8, color: COLORS.indigo900 },
    ];

    tableBoxes.forEach((t) => {
        slide.addShape(pptx.ShapeType.rect, {
            x: t.x,
            y: t.y,
            w: t.w,
            h: 0.65,
            fill: { color: t.color },
        });
        slide.addText(t.name, {
            x: t.x,
            y: t.y,
            w: t.w,
            h: 0.65,
            fontSize: 9.5,
            bold: true,
            color: COLORS.white,
            fontFace: 'Courier New',
            align: 'center',
            valign: 'middle',
        });
    });

    const relations = [
        { label: '1→*', x1: 3.1, y1: 1.72, x2: 3.6, y2: 1.72 },
        { label: '1→*', x1: 3.1, y1: 3.32, x2: 3.6, y2: 3.32 },
        { label: '1→*', x1: 3.1, y1: 4.92, x2: 3.6, y2: 4.92 },
        { label: '1→*', x1: 6.4, y1: 1.72, x2: 6.9, y2: 1.72 },
        { label: '1→*', x1: 6.4, y1: 3.32, x2: 6.9, y2: 3.32 },
        { label: '1→*', x1: 9.7, y1: 1.72, x2: 10.2, y2: 1.72 },
        { label: '1→*', x1: 9.7, y1: 3.32, x2: 10.2, y2: 3.32 },
    ];

    relations.forEach((r) => {
        slide.addShape(pptx.ShapeType.line, {
            x: r.x1,
            y: r.y1,
            w: r.x2 - r.x1,
            h: 0,
            line: { color: COLORS.gray400, width: 1 },
        });
        slide.addText(r.label, {
            x: r.x1 - 0.1,
            y: r.y1 - 0.18,
            w: 0.5,
            h: 0.25,
            fontSize: 7.5,
            color: COLORS.gray600,
            fontFace: FONTS.body,
        });
    });

    slide.addText(
        'Key design: Students access assessments through enrollment.\n' +
            'assessment_assignments links enrollments ↔ assessments (not users directly).\n' +
            'Scores are stored per answer, not per assignment.',
        {
            x: 0.3,
            y: 6.35,
            w: 12.7,
            h: 0.65,
            fontSize: 10,
            color: COLORS.indigo900,
            fontFace: FONTS.body,
            italic: true,
        },
    );
}

// ─────────────────────────────────────────────
// SLIDE 11 — Security & Authorization
// ─────────────────────────────────────────────
{
    const slide = pptx.addSlide();
    slide.addShape(pptx.ShapeType.rect, {
        x: 0,
        y: 0,
        w: '100%',
        h: '100%',
        fill: { color: COLORS.white },
    });

    addHeader(slide, 'Security & Authorization');
    addFooter(slide, 11);

    slide.addShape(pptx.ShapeType.rect, {
        x: 0.35,
        y: 1.3,
        w: 6.0,
        h: 5.6,
        fill: { color: COLORS.indigo50 },
        line: { color: COLORS.indigo400, width: 1.5 },
    });

    slide.addText('Exam Security', {
        x: 0.35,
        y: 1.3,
        w: 6.0,
        h: 0.55,
        fontSize: 13,
        bold: true,
        color: COLORS.indigo900,
        fontFace: FONTS.heading,
        align: 'center',
        valign: 'middle',
    });

    const secFeatures = [
        ['Fullscreen enforcement', 'Assessment hides if student exits fullscreen'],
        ['Tab switch detection', 'Any focus loss is recorded as a violation'],
        ['DevTools detection', 'Opening browser devtools triggers a flag'],
        ['Time-bound sessions', 'Auto-submit on duration_minutes expiry'],
        ['Forced submission flag', 'forced_submission = true on timeout/violation'],
        ['Violation logging', 'security_violation column records violation type'],
        ['Dev mode bypass', 'EXAM_DEV_MODE=true disables all security for local dev'],
    ];

    secFeatures.forEach((f, i) => {
        slide.addShape(pptx.ShapeType.rect, {
            x: 0.5,
            y: 2.0 + i * 0.68,
            w: 5.7,
            h: 0.6,
            fill: { color: i % 2 === 0 ? COLORS.white : COLORS.indigo50 },
            line: { color: COLORS.indigo200, width: 0.5 },
        });
        slide.addText(f[0], {
            x: 0.65,
            y: 2.0 + i * 0.68,
            w: 2.5,
            h: 0.6,
            fontSize: 10,
            bold: true,
            color: COLORS.indigo900,
            fontFace: FONTS.body,
            valign: 'middle',
        });
        slide.addText(f[1], {
            x: 3.2,
            y: 2.0 + i * 0.68,
            w: 3.0,
            h: 0.6,
            fontSize: 9.5,
            color: COLORS.gray600,
            fontFace: FONTS.body,
            valign: 'middle',
        });
    });

    slide.addShape(pptx.ShapeType.rect, {
        x: 6.7,
        y: 1.3,
        w: 6.45,
        h: 5.6,
        fill: { color: COLORS.indigo50 },
        line: { color: COLORS.indigo400, width: 1.5 },
    });

    slide.addText('Hybrid Authorization Model', {
        x: 6.7,
        y: 1.3,
        w: 6.45,
        h: 0.55,
        fontSize: 13,
        bold: true,
        color: COLORS.indigo900,
        fontFace: FONTS.heading,
        align: 'center',
        valign: 'middle',
    });

    const authPoints = [
        {
            title: 'Role: student',
            desc: 'STRICT role check via middleware. All /student/* routes require role:student.',
            color: COLORS.amber500,
        },
        {
            title: 'Policies (other roles)',
            desc: 'Business authorization via Laravel Policies. Controllers always call $this->authorize() before service methods.',
            color: COLORS.indigo600,
        },
        {
            title: 'Spatie Permissions',
            desc: 'Granular permissions assigned to roles. Passed to frontend via Inertia props for conditional UI.',
            color: COLORS.green600,
        },
        {
            title: 'Enrollment gate',
            desc: 'Students can only access assessments tied to their active enrollment. No direct assignment creation.',
            color: COLORS.blue500,
        },
    ];

    authPoints.forEach((a, i) => {
        slide.addShape(pptx.ShapeType.rect, {
            x: 6.85,
            y: 2.05 + i * 1.15,
            w: 0.35,
            h: 0.9,
            fill: { color: a.color },
        });

        slide.addText(a.title, {
            x: 7.3,
            y: 2.05 + i * 1.15,
            w: 5.7,
            h: 0.38,
            fontSize: 11,
            bold: true,
            color: COLORS.indigo900,
            fontFace: FONTS.heading,
            valign: 'middle',
        });

        slide.addText(a.desc, {
            x: 7.3,
            y: 2.43 + i * 1.15,
            w: 5.7,
            h: 0.55,
            fontSize: 9.5,
            color: COLORS.gray600,
            fontFace: FONTS.body,
        });
    });
}

// ─────────────────────────────────────────────
// SLIDE 12 — Key Design Decisions
// ─────────────────────────────────────────────
{
    const slide = pptx.addSlide();
    slide.addShape(pptx.ShapeType.rect, {
        x: 0,
        y: 0,
        w: '100%',
        h: '100%',
        fill: { color: COLORS.gray50 },
    });

    addHeader(slide, 'Key Design Decisions');
    addFooter(slide, 12);

    const decisions = [
        {
            title: 'Service-Oriented Architecture',
            decision:
                'Controllers are thin — all business logic lives in dedicated Services (SRP).',
            why: 'Testability, maintainability, and clear separation of concerns across Admin, Teacher, Student, Core domains.',
        },
        {
            title: 'Enrollment-Based Assessment Access',
            decision:
                'Students access assessments through class enrollment, not direct user assignment.',
            why: 'Reflects real-world academic structure. Changing a class enrollment automatically affects all assessment access.',
        },
        {
            title: 'Scores per Answer',
            decision: 'Score is stored on each Answer row, not a total on AssessmentAssignment.',
            why: 'Enables per-question grading, partial corrections, and flexible recalculation without migration.',
        },
        {
            title: 'File Metadata in Answers',
            decision:
                'File fields (name, path, size, mime_type) embedded directly in the answers table.',
            why: 'Keeps queries simple — one join instead of a separate attachments table for the majority of use cases.',
        },
        {
            title: 'Strategy Pattern for Validation',
            decision:
                'Question and score validation use Strategy pattern with swappable validator classes.',
            why: 'Each question type has different rules. Adding a new type only requires a new Strategy class — open/closed principle.',
        },
        {
            title: 'Soft Deletes on Users & Assessments only',
            decision:
                'Only users and assessments support soft deletes. Other entities are hard-deleted.',
            why: 'Preserves audit trail for gradeable content and user data, without adding deleted_at overhead to every table.',
        },
    ];

    decisions.forEach((d, i) => {
        const col = i % 2;
        const row = Math.floor(i / 2);
        const x = 0.35 + col * 6.5;
        const y = 1.3 + row * 2.0;

        slide.addShape(pptx.ShapeType.rect, {
            x,
            y,
            w: 6.2,
            h: 1.85,
            fill: { color: COLORS.white },
            line: { color: COLORS.indigo200, width: 1 },
            shadow: { type: 'outer', color: 'dddddd', blur: 4, offset: 2, angle: 270 },
        });

        slide.addShape(pptx.ShapeType.rect, {
            x,
            y,
            w: 0.18,
            h: 1.85,
            fill: { color: COLORS.indigo600 },
        });

        slide.addText(d.title, {
            x: x + 0.28,
            y: y + 0.1,
            w: 5.8,
            h: 0.38,
            fontSize: 11,
            bold: true,
            color: COLORS.indigo900,
            fontFace: FONTS.heading,
        });

        slide.addText('Decision: ' + d.decision, {
            x: x + 0.28,
            y: y + 0.5,
            w: 5.8,
            h: 0.55,
            fontSize: 9.5,
            color: COLORS.gray700,
            fontFace: FONTS.body,
        });

        slide.addText('Why: ' + d.why, {
            x: x + 0.28,
            y: y + 1.08,
            w: 5.8,
            h: 0.65,
            fontSize: 9,
            italic: true,
            color: COLORS.indigo600,
            fontFace: FONTS.body,
        });
    });
}

// ─────────────────────────────────────────────
// SLIDE 13 — Closing / Q&A
// ─────────────────────────────────────────────
{
    const slide = pptx.addSlide();

    slide.addShape(pptx.ShapeType.rect, {
        x: 0,
        y: 0,
        w: '100%',
        h: '100%',
        fill: { color: COLORS.indigo900 },
    });

    slide.addShape(pptx.ShapeType.rect, {
        x: 0,
        y: 5.8,
        w: '100%',
        h: 1.7,
        fill: { color: COLORS.indigo800 },
    });

    slide.addShape(pptx.ShapeType.rect, {
        x: 0,
        y: 5.75,
        w: '100%',
        h: 0.08,
        fill: { color: COLORS.indigo400 },
    });

    addGem(slide, 5.6, 0.5, 0.85);

    slide.addText('Thank You', {
        x: 0,
        y: 1.9,
        w: '100%',
        h: 1.0,
        fontSize: 60,
        bold: true,
        color: COLORS.white,
        fontFace: FONTS.heading,
        align: 'center',
    });

    slide.addText('Questions & Discussion', {
        x: 0,
        y: 3.0,
        w: '100%',
        h: 0.55,
        fontSize: 20,
        color: COLORS.indigo200,
        fontFace: FONTS.body,
        align: 'center',
    });

    slide.addShape(pptx.ShapeType.line, {
        x: 3.5,
        y: 3.7,
        w: 6.2,
        h: 0,
        line: { color: COLORS.indigo500, width: 1.5 },
    });

    const summary = [
        'Laravel 12 + React 18 + Inertia.js v2',
        '3 roles  ·  5 question types  ·  2 delivery modes',
        'Enrollment-based access  ·  Per-answer scoring',
        'Strategy pattern validation  ·  Policy-based authorization',
        'PHPUnit + Vitest + Playwright  ·  ≥ 70% coverage',
    ];

    summary.forEach((line, i) => {
        slide.addText(line, {
            x: 0,
            y: 3.95 + i * 0.35,
            w: '100%',
            h: 0.35,
            fontSize: 11,
            color: COLORS.indigo300 ?? COLORS.indigo400,
            fontFace: FONTS.body,
            align: 'center',
        });
    });

    slide.addText('Evalium  —  Where every grade tells a story', {
        x: 0,
        y: 6.1,
        w: '100%',
        h: 0.55,
        fontSize: 12,
        italic: true,
        color: COLORS.indigo200,
        fontFace: FONTS.body,
        align: 'center',
    });
}

// ─────────────────────────────────────────────
// Generate file
// ─────────────────────────────────────────────
await pptx.writeFile({ fileName: 'Evalium-Presentation.pptx' });
console.log('Evalium-Presentation.pptx generated successfully.');
