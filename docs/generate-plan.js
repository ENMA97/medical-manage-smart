const {
  Document, Packer, Paragraph, TextRun, Table, TableRow, TableCell,
  HeadingLevel, AlignmentType, BorderStyle, WidthType, ShadingType
} = require('docx');
const fs = require('fs');

// Common styles
const border = { style: BorderStyle.SINGLE, size: 1, color: "CCCCCC" };
const borders = { top: border, bottom: border, left: border, right: border };
const cellMargins = { top: 80, bottom: 80, left: 120, right: 120 };
const headerShading = { fill: "1E3A5F", type: ShadingType.CLEAR };
const altRowShading = { fill: "F8FAFC", type: ShadingType.CLEAR };

function createHeaderCell(text, width) {
  return new TableCell({
    borders,
    width: { size: width, type: WidthType.DXA },
    shading: headerShading,
    margins: cellMargins,
    children: [new Paragraph({
      children: [new TextRun({ text, bold: true, color: "FFFFFF", font: "Arial", size: 22 })]
    })]
  });
}

function createCell(text, width, shading = null) {
  return new TableCell({
    borders,
    width: { size: width, type: WidthType.DXA },
    shading: shading,
    margins: cellMargins,
    children: [new Paragraph({
      children: [new TextRun({ text, font: "Arial", size: 20 })]
    })]
  });
}

const doc = new Document({
  styles: {
    default: { document: { run: { font: "Arial", size: 24 } } },
    paragraphStyles: [
      { id: "Heading1", name: "Heading 1", basedOn: "Normal", next: "Normal", quickFormat: true,
        run: { size: 36, bold: true, font: "Arial", color: "1E3A5F" },
        paragraph: { spacing: { before: 400, after: 200 }, alignment: AlignmentType.RIGHT, outlineLevel: 0 } },
      { id: "Heading2", name: "Heading 2", basedOn: "Normal", next: "Normal", quickFormat: true,
        run: { size: 28, bold: true, font: "Arial", color: "2563EB" },
        paragraph: { spacing: { before: 300, after: 150 }, alignment: AlignmentType.RIGHT, outlineLevel: 1 } },
      { id: "Heading3", name: "Heading 3", basedOn: "Normal", next: "Normal", quickFormat: true,
        run: { size: 24, bold: true, font: "Arial", color: "374151" },
        paragraph: { spacing: { before: 200, after: 100 }, alignment: AlignmentType.RIGHT, outlineLevel: 2 } },
    ]
  },
  sections: [{
    properties: {
      page: {
        size: { width: 12240, height: 15840 },
        margin: { top: 1440, right: 1440, bottom: 1440, left: 1440 }
      }
    },
    children: [
      // Title
      new Paragraph({
        heading: HeadingLevel.HEADING_1,
        alignment: AlignmentType.CENTER,
        children: [new TextRun({ text: "خطة التنفيذ التفصيلية", bold: true, size: 48 })]
      }),
      new Paragraph({
        alignment: AlignmentType.CENTER,
        spacing: { after: 400 },
        children: [new TextRun({ text: "نظام تخطيط الموارد الطبية الذكي (Medical ERP Smart)", size: 28, color: "6B7280" })]
      }),
      new Paragraph({
        alignment: AlignmentType.CENTER,
        spacing: { after: 600 },
        children: [new TextRun({ text: "تاريخ الإعداد: 24 يناير 2026", size: 22, color: "9CA3AF" })]
      }),

      // Executive Summary
      new Paragraph({ heading: HeadingLevel.HEADING_1, children: [new TextRun("1. الملخص التنفيذي")] }),
      new Paragraph({
        alignment: AlignmentType.JUSTIFIED,
        bidi: true,
        spacing: { after: 200 },
        children: [new TextRun({
          text: "يهدف هذا المشروع إلى بناء نظام ERP سحابي متكامل لإدارة المنشآت الطبية، يعتمد على أحدث التقنيات والذكاء الاصطناعي. تم تقسيم المشروع إلى 4 مراحل رئيسية بمدة إجمالية 24 أسبوعاً (6 أشهر).",
          size: 22
        })]
      }),

      // Project Metrics Table
      new Paragraph({ heading: HeadingLevel.HEADING_2, children: [new TextRun("مؤشرات المشروع الرئيسية")] }),
      new Table({
        width: { size: 100, type: WidthType.PERCENTAGE },
        columnWidths: [2340, 2340, 2340, 2340],
        rows: [
          new TableRow({
            children: [
              createHeaderCell("المؤشر", 2340),
              createHeaderCell("القيمة", 2340),
              createHeaderCell("المؤشر", 2340),
              createHeaderCell("القيمة", 2340),
            ]
          }),
          new TableRow({
            children: [
              createCell("المدة الإجمالية", 2340),
              createCell("24 أسبوع", 2340),
              createCell("عدد المراحل", 2340),
              createCell("4 مراحل", 2340),
            ]
          }),
          new TableRow({
            children: [
              createCell("عدد الوحدات", 2340, altRowShading),
              createCell("5 وحدات رئيسية", 2340, altRowShading),
              createCell("تغطية الاختبارات", 2340, altRowShading),
              createCell("≥ 80%", 2340, altRowShading),
            ]
          }),
        ]
      }),
      new Paragraph({ spacing: { after: 400 }, children: [] }),

      // Phase 1
      new Paragraph({ heading: HeadingLevel.HEADING_1, children: [new TextRun("2. المرحلة الأولى: البنية التحتية والأساسيات")] }),
      new Paragraph({
        spacing: { after: 200 },
        children: [new TextRun({ text: "المدة: 6 أسابيع (الأسبوع 1-6)", bold: true, color: "059669" })]
      }),

      new Paragraph({ heading: HeadingLevel.HEADING_3, children: [new TextRun("2.1 الأسبوع 1-2: إعداد البيئة")] }),
      new Table({
        width: { size: 100, type: WidthType.PERCENTAGE },
        columnWidths: [1500, 5000, 1500, 1360],
        rows: [
          new TableRow({
            children: [
              createHeaderCell("#", 1500),
              createHeaderCell("المهمة", 5000),
              createHeaderCell("المسؤول", 1500),
              createHeaderCell("الحالة", 1360),
            ]
          }),
          new TableRow({
            children: [
              createCell("1", 1500),
              createCell("إعداد مستودع GitHub مع branching strategy", 5000),
              createCell("DevOps", 1500),
              createCell("⬜", 1360),
            ]
          }),
          new TableRow({
            children: [
              createCell("2", 1500, altRowShading),
              createCell("تهيئة Docker Compose للبيئات (dev, staging, prod)", 5000, altRowShading),
              createCell("DevOps", 1500, altRowShading),
              createCell("⬜", 1360, altRowShading),
            ]
          }),
          new TableRow({
            children: [
              createCell("3", 1500),
              createCell("إنشاء مشروع Laravel 11 مع PHP 8.2+", 5000),
              createCell("Backend", 1500),
              createCell("⬜", 1360),
            ]
          }),
          new TableRow({
            children: [
              createCell("4", 1500, altRowShading),
              createCell("إنشاء مشروع React + Vite + Tailwind CSS", 5000, altRowShading),
              createCell("Frontend", 1500, altRowShading),
              createCell("⬜", 1360, altRowShading),
            ]
          }),
          new TableRow({
            children: [
              createCell("5", 1500),
              createCell("إعداد PostgreSQL مع UUID extension", 5000),
              createCell("Backend", 1500),
              createCell("⬜", 1360),
            ]
          }),
        ]
      }),
      new Paragraph({ spacing: { after: 300 }, children: [] }),

      new Paragraph({ heading: HeadingLevel.HEADING_3, children: [new TextRun("2.2 الأسبوع 3-4: قاعدة البيانات والأمان")] }),
      new Table({
        width: { size: 100, type: WidthType.PERCENTAGE },
        columnWidths: [1500, 5000, 1500, 1360],
        rows: [
          new TableRow({
            children: [
              createHeaderCell("#", 1500),
              createHeaderCell("المهمة", 5000),
              createHeaderCell("المسؤول", 1500),
              createHeaderCell("الحالة", 1360),
            ]
          }),
          new TableRow({
            children: [
              createCell("1", 1500),
              createCell("تنفيذ جميع Laravel Migrations", 5000),
              createCell("Backend", 1500),
              createCell("⬜", 1360),
            ]
          }),
          new TableRow({
            children: [
              createCell("2", 1500, altRowShading),
              createCell("إنشاء نظام الصلاحيات RBAC", 5000, altRowShading),
              createCell("Backend", 1500, altRowShading),
              createCell("⬜", 1360, altRowShading),
            ]
          }),
          new TableRow({
            children: [
              createCell("3", 1500),
              createCell("بناء نظام المصادقة JWT/Sanctum", 5000),
              createCell("Backend", 1500),
              createCell("⬜", 1360),
            ]
          }),
          new TableRow({
            children: [
              createCell("4", 1500, altRowShading),
              createCell("تنفيذ Audit Trail للعمليات الحساسة", 5000, altRowShading),
              createCell("Backend", 1500, altRowShading),
              createCell("⬜", 1360, altRowShading),
            ]
          }),
        ]
      }),
      new Paragraph({ spacing: { after: 300 }, children: [] }),

      new Paragraph({ heading: HeadingLevel.HEADING_3, children: [new TextRun("2.3 الأسبوع 5-6: الواجهة الأساسية")] }),
      new Table({
        width: { size: 100, type: WidthType.PERCENTAGE },
        columnWidths: [1500, 5000, 1500, 1360],
        rows: [
          new TableRow({
            children: [
              createHeaderCell("#", 1500),
              createHeaderCell("المهمة", 5000),
              createHeaderCell("المسؤول", 1500),
              createHeaderCell("الحالة", 1360),
            ]
          }),
          new TableRow({
            children: [
              createCell("1", 1500),
              createCell("تصميم Layout الرئيسي (RTL + Sidebar)", 5000),
              createCell("Frontend", 1500),
              createCell("⬜", 1360),
            ]
          }),
          new TableRow({
            children: [
              createCell("2", 1500, altRowShading),
              createCell("بناء مكونات UI المشتركة (Table, Form, Modal)", 5000, altRowShading),
              createCell("Frontend", 1500, altRowShading),
              createCell("⬜", 1360, altRowShading),
            ]
          }),
          new TableRow({
            children: [
              createCell("3", 1500),
              createCell("تنفيذ شاشة تسجيل الدخول", 5000),
              createCell("Frontend", 1500),
              createCell("⬜", 1360),
            ]
          }),
          new TableRow({
            children: [
              createCell("4", 1500, altRowShading),
              createCell("إعداد React Query للـ API calls", 5000, altRowShading),
              createCell("Frontend", 1500, altRowShading),
              createCell("⬜", 1360, altRowShading),
            ]
          }),
        ]
      }),
      new Paragraph({ spacing: { after: 400 }, children: [] }),

      // Phase 2
      new Paragraph({ heading: HeadingLevel.HEADING_1, children: [new TextRun("3. المرحلة الثانية: الوحدات الأساسية")] }),
      new Paragraph({
        spacing: { after: 200 },
        children: [new TextRun({ text: "المدة: 8 أسابيع (الأسبوع 7-14)", bold: true, color: "059669" })]
      }),

      new Paragraph({ heading: HeadingLevel.HEADING_3, children: [new TextRun("3.1 الأسبوع 7-9: وحدة الموارد البشرية (HR)")] }),
      new Table({
        width: { size: 100, type: WidthType.PERCENTAGE },
        columnWidths: [1500, 5000, 1500, 1360],
        rows: [
          new TableRow({
            children: [
              createHeaderCell("#", 1500),
              createHeaderCell("المهمة", 5000),
              createHeaderCell("المسؤول", 1500),
              createHeaderCell("الحالة", 1360),
            ]
          }),
          new TableRow({
            children: [
              createCell("1", 1500),
              createCell("CRUD الموظفين مع البحث والفلترة", 5000),
              createCell("Full Stack", 1500),
              createCell("⬜", 1360),
            ]
          }),
          new TableRow({
            children: [
              createCell("2", 1500, altRowShading),
              createCell("إدارة العقود (5 أنواع)", 5000, altRowShading),
              createCell("Full Stack", 1500, altRowShading),
              createCell("⬜", 1360, altRowShading),
            ]
          }),
          new TableRow({
            children: [
              createCell("3", 1500),
              createCell("نظام العهد (Custody) مع التتبع", 5000),
              createCell("Full Stack", 1500),
              createCell("⬜", 1360),
            ]
          }),
          new TableRow({
            children: [
              createCell("4", 1500, altRowShading),
              createCell("إخلاء الطرف مع شرط تسليم العهدة", 5000, altRowShading),
              createCell("Full Stack", 1500, altRowShading),
              createCell("⬜", 1360, altRowShading),
            ]
          }),
        ]
      }),
      new Paragraph({ spacing: { after: 300 }, children: [] }),

      new Paragraph({ heading: HeadingLevel.HEADING_3, children: [new TextRun("3.2 الأسبوع 10-12: وحدة المستودعات (Inventory)")] }),
      new Table({
        width: { size: 100, type: WidthType.PERCENTAGE },
        columnWidths: [1500, 5000, 1500, 1360],
        rows: [
          new TableRow({
            children: [
              createHeaderCell("#", 1500),
              createHeaderCell("المهمة", 5000),
              createHeaderCell("المسؤول", 1500),
              createHeaderCell("الحالة", 1360),
            ]
          }),
          new TableRow({
            children: [
              createCell("1", 1500),
              createCell("إدارة المستودعات ومراكز التكلفة", 5000),
              createCell("Full Stack", 1500),
              createCell("⬜", 1360),
            ]
          }),
          new TableRow({
            children: [
              createCell("2", 1500, altRowShading),
              createCell("نظام الأصناف مع Batch tracking", 5000, altRowShading),
              createCell("Full Stack", 1500, altRowShading),
              createCell("⬜", 1360, altRowShading),
            ]
          }),
          new TableRow({
            children: [
              createCell("3", 1500),
              createCell("تنفيذ سياسة FEFO للصرف", 5000),
              createCell("Backend", 1500),
              createCell("⬜", 1360),
            ]
          }),
          new TableRow({
            children: [
              createCell("4", 1500, altRowShading),
              createCell("Optimistic Locking لمنع التضارب", 5000, altRowShading),
              createCell("Backend", 1500, altRowShading),
              createCell("⬜", 1360, altRowShading),
            ]
          }),
          new TableRow({
            children: [
              createCell("5", 1500),
              createCell("نظام الحصص اليومية (Quotas)", 5000),
              createCell("Full Stack", 1500),
              createCell("⬜", 1360),
            ]
          }),
          new TableRow({
            children: [
              createCell("6", 1500, altRowShading),
              createCell("الكراش كار مع محضر Blue Code", 5000, altRowShading),
              createCell("Full Stack", 1500, altRowShading),
              createCell("⬜", 1360, altRowShading),
            ]
          }),
        ]
      }),
      new Paragraph({ spacing: { after: 300 }, children: [] }),

      new Paragraph({ heading: HeadingLevel.HEADING_3, children: [new TextRun("3.3 الأسبوع 13-14: وحدة الجداول والمناوبات")] }),
      new Table({
        width: { size: 100, type: WidthType.PERCENTAGE },
        columnWidths: [1500, 5000, 1500, 1360],
        rows: [
          new TableRow({
            children: [
              createHeaderCell("#", 1500),
              createHeaderCell("المهمة", 5000),
              createHeaderCell("المسؤول", 1500),
              createHeaderCell("الحالة", 1360),
            ]
          }),
          new TableRow({
            children: [
              createCell("1", 1500),
              createCell("تقويم المناوبات (Drag & Drop)", 5000),
              createCell("Frontend", 1500),
              createCell("⬜", 1360),
            ]
          }),
          new TableRow({
            children: [
              createCell("2", 1500, altRowShading),
              createCell("أنماط الدوام (Single, Split, On-Call)", 5000, altRowShading),
              createCell("Full Stack", 1500, altRowShading),
              createCell("⬜", 1360, altRowShading),
            ]
          }),
          new TableRow({
            children: [
              createCell("3", 1500),
              createCell("التحقق من تغطية التعقيم", 5000),
              createCell("Backend", 1500),
              createCell("⬜", 1360),
            ]
          }),
          new TableRow({
            children: [
              createCell("4", 1500, altRowShading),
              createCell("كشف الثغرات (Gap Analysis)", 5000, altRowShading),
              createCell("Backend", 1500, altRowShading),
              createCell("⬜", 1360, altRowShading),
            ]
          }),
        ]
      }),
      new Paragraph({ spacing: { after: 400 }, children: [] }),

      // Phase 3
      new Paragraph({ heading: HeadingLevel.HEADING_1, children: [new TextRun("4. المرحلة الثالثة: المالية والذكاء الاصطناعي")] }),
      new Paragraph({
        spacing: { after: 200 },
        children: [new TextRun({ text: "المدة: 6 أسابيع (الأسبوع 15-20)", bold: true, color: "059669" })]
      }),

      new Paragraph({ heading: HeadingLevel.HEADING_3, children: [new TextRun("4.1 الأسبوع 15-17: الوحدة المالية")] }),
      new Table({
        width: { size: 100, type: WidthType.PERCENTAGE },
        columnWidths: [1500, 5000, 1500, 1360],
        rows: [
          new TableRow({
            children: [
              createHeaderCell("#", 1500),
              createHeaderCell("المهمة", 5000),
              createHeaderCell("المسؤول", 1500),
              createHeaderCell("الحالة", 1360),
            ]
          }),
          new TableRow({
            children: [
              createCell("1", 1500),
              createCell("مراكز التكلفة وتوزيع المصاريف (ABC)", 5000),
              createCell("Backend", 1500),
              createCell("⬜", 1360),
            ]
          }),
          new TableRow({
            children: [
              createCell("2", 1500, altRowShading),
              createCell("حساب ربحية العيادات والخدمات", 5000, altRowShading),
              createCell("Full Stack", 1500, altRowShading),
              createCell("⬜", 1360, altRowShading),
            ]
          }),
          new TableRow({
            children: [
              createCell("3", 1500),
              createCell("نظام التأمين (Claims, Scrubber)", 5000),
              createCell("Full Stack", 1500),
              createCell("⬜", 1360),
            ]
          }),
          new TableRow({
            children: [
              createCell("4", 1500, altRowShading),
              createCell("تقرير أعمار الديون (Aging)", 5000, altRowShading),
              createCell("Backend", 1500, altRowShading),
              createCell("⬜", 1360, altRowShading),
            ]
          }),
          new TableRow({
            children: [
              createCell("5", 1500),
              createCell("نظام Clawback للعمولات", 5000),
              createCell("Full Stack", 1500),
              createCell("⬜", 1360),
            ]
          }),
        ]
      }),
      new Paragraph({ spacing: { after: 300 }, children: [] }),

      new Paragraph({ heading: HeadingLevel.HEADING_3, children: [new TextRun("4.2 الأسبوع 18-19: الرواتب (WPS)")] }),
      new Table({
        width: { size: 100, type: WidthType.PERCENTAGE },
        columnWidths: [1500, 5000, 1500, 1360],
        rows: [
          new TableRow({
            children: [
              createHeaderCell("#", 1500),
              createHeaderCell("المهمة", 5000),
              createHeaderCell("المسؤول", 1500),
              createHeaderCell("الحالة", 1360),
            ]
          }),
          new TableRow({
            children: [
              createCell("1", 1500),
              createCell("حساب الرواتب الشهرية آلياً", 5000),
              createCell("Backend", 1500),
              createCell("⬜", 1360),
            ]
          }),
          new TableRow({
            children: [
              createCell("2", 1500, altRowShading),
              createCell("احتساب الإضافي والخصومات", 5000, altRowShading),
              createCell("Backend", 1500, altRowShading),
              createCell("⬜", 1360, altRowShading),
            ]
          }),
          new TableRow({
            children: [
              createCell("3", 1500),
              createCell("توليد ملف WPS", 5000),
              createCell("Backend", 1500),
              createCell("⬜", 1360),
            ]
          }),
          new TableRow({
            children: [
              createCell("4", 1500, altRowShading),
              createCell("إدارة السلف والأقساط", 5000, altRowShading),
              createCell("Full Stack", 1500, altRowShading),
              createCell("⬜", 1360, altRowShading),
            ]
          }),
        ]
      }),
      new Paragraph({ spacing: { after: 300 }, children: [] }),

      new Paragraph({ heading: HeadingLevel.HEADING_3, children: [new TextRun("4.3 الأسبوع 20: دمج Gemini AI")] }),
      new Table({
        width: { size: 100, type: WidthType.PERCENTAGE },
        columnWidths: [1500, 5000, 1500, 1360],
        rows: [
          new TableRow({
            children: [
              createHeaderCell("#", 1500),
              createHeaderCell("المهمة", 5000),
              createHeaderCell("المسؤول", 1500),
              createHeaderCell("الحالة", 1360),
            ]
          }),
          new TableRow({
            children: [
              createCell("1", 1500),
              createCell("المحلل المالي: تقارير ذكية", 5000),
              createCell("Backend", 1500),
              createCell("⬜", 1360),
            ]
          }),
          new TableRow({
            children: [
              createCell("2", 1500, altRowShading),
              createCell("مساعد صياغة Medical Justification", 5000, altRowShading),
              createCell("Full Stack", 1500, altRowShading),
              createCell("⬜", 1360, altRowShading),
            ]
          }),
          new TableRow({
            children: [
              createCell("3", 1500),
              createCell("تحليل المخزون واقتراح العروض", 5000),
              createCell("Full Stack", 1500),
              createCell("⬜", 1360),
            ]
          }),
        ]
      }),
      new Paragraph({ spacing: { after: 400 }, children: [] }),

      // Phase 4
      new Paragraph({ heading: HeadingLevel.HEADING_1, children: [new TextRun("5. المرحلة الرابعة: التكامل والإطلاق")] }),
      new Paragraph({
        spacing: { after: 200 },
        children: [new TextRun({ text: "المدة: 4 أسابيع (الأسبوع 21-24)", bold: true, color: "059669" })]
      }),

      new Paragraph({ heading: HeadingLevel.HEADING_3, children: [new TextRun("5.1 الأسبوع 21-22: التكامل والاختبار")] }),
      new Table({
        width: { size: 100, type: WidthType.PERCENTAGE },
        columnWidths: [1500, 5000, 1500, 1360],
        rows: [
          new TableRow({
            children: [
              createHeaderCell("#", 1500),
              createHeaderCell("المهمة", 5000),
              createHeaderCell("المسؤول", 1500),
              createHeaderCell("الحالة", 1360),
            ]
          }),
          new TableRow({
            children: [
              createCell("1", 1500),
              createCell("ربط أجهزة البصمة ZKTeco", 5000),
              createCell("Backend", 1500),
              createCell("⬜", 1360),
            ]
          }),
          new TableRow({
            children: [
              createCell("2", 1500, altRowShading),
              createCell("تنفيذ PWA + Offline Mode", 5000, altRowShading),
              createCell("Frontend", 1500, altRowShading),
              createCell("⬜", 1360, altRowShading),
            ]
          }),
          new TableRow({
            children: [
              createCell("3", 1500),
              createCell("Unit Tests (تغطية ≥ 80%)", 5000),
              createCell("Full Stack", 1500),
              createCell("⬜", 1360),
            ]
          }),
          new TableRow({
            children: [
              createCell("4", 1500, altRowShading),
              createCell("Integration Tests", 5000, altRowShading),
              createCell("QA", 1500, altRowShading),
              createCell("⬜", 1360, altRowShading),
            ]
          }),
        ]
      }),
      new Paragraph({ spacing: { after: 300 }, children: [] }),

      new Paragraph({ heading: HeadingLevel.HEADING_3, children: [new TextRun("5.2 الأسبوع 23-24: التوثيق والإطلاق")] }),
      new Table({
        width: { size: 100, type: WidthType.PERCENTAGE },
        columnWidths: [1500, 5000, 1500, 1360],
        rows: [
          new TableRow({
            children: [
              createHeaderCell("#", 1500),
              createHeaderCell("المهمة", 5000),
              createHeaderCell("المسؤول", 1500),
              createHeaderCell("الحالة", 1360),
            ]
          }),
          new TableRow({
            children: [
              createCell("1", 1500),
              createCell("بناء Desktop App (NativePHP)", 5000),
              createCell("Backend", 1500),
              createCell("⬜", 1360),
            ]
          }),
          new TableRow({
            children: [
              createCell("2", 1500, altRowShading),
              createCell("إعداد Swagger API Documentation", 5000, altRowShading),
              createCell("Backend", 1500, altRowShading),
              createCell("⬜", 1360, altRowShading),
            ]
          }),
          new TableRow({
            children: [
              createCell("3", 1500),
              createCell("دليل المستخدم PDF (عربي/إنجليزي)", 5000),
              createCell("Technical Writer", 1500),
              createCell("⬜", 1360),
            ]
          }),
          new TableRow({
            children: [
              createCell("4", 1500, altRowShading),
              createCell("UAT مع العميل", 5000, altRowShading),
              createCell("PM", 1500, altRowShading),
              createCell("⬜", 1360, altRowShading),
            ]
          }),
          new TableRow({
            children: [
              createCell("5", 1500),
              createCell("الإطلاق على Production", 5000),
              createCell("DevOps", 1500),
              createCell("⬜", 1360),
            ]
          }),
        ]
      }),
      new Paragraph({ spacing: { after: 400 }, children: [] }),

      // Team Structure
      new Paragraph({ heading: HeadingLevel.HEADING_1, children: [new TextRun("6. هيكل الفريق المقترح")] }),
      new Table({
        width: { size: 100, type: WidthType.PERCENTAGE },
        columnWidths: [3120, 3120, 3120],
        rows: [
          new TableRow({
            children: [
              createHeaderCell("الدور", 3120),
              createHeaderCell("العدد", 3120),
              createHeaderCell("المهارات المطلوبة", 3120),
            ]
          }),
          new TableRow({
            children: [
              createCell("Project Manager", 3120),
              createCell("1", 3120),
              createCell("Agile, Healthcare domain", 3120),
            ]
          }),
          new TableRow({
            children: [
              createCell("Backend Developer", 3120, altRowShading),
              createCell("2", 3120, altRowShading),
              createCell("Laravel, PostgreSQL, API design", 3120, altRowShading),
            ]
          }),
          new TableRow({
            children: [
              createCell("Frontend Developer", 3120),
              createCell("2", 3120),
              createCell("React, Tailwind, RTL", 3120),
            ]
          }),
          new TableRow({
            children: [
              createCell("DevOps Engineer", 3120, altRowShading),
              createCell("1", 3120, altRowShading),
              createCell("Docker, CI/CD, AWS/GCP", 3120, altRowShading),
            ]
          }),
          new TableRow({
            children: [
              createCell("QA Engineer", 3120),
              createCell("1", 3120),
              createCell("Manual + Automated testing", 3120),
            ]
          }),
        ]
      }),
      new Paragraph({ spacing: { after: 400 }, children: [] }),

      // Risks
      new Paragraph({ heading: HeadingLevel.HEADING_1, children: [new TextRun("7. المخاطر والإجراءات الوقائية")] }),
      new Table({
        width: { size: 100, type: WidthType.PERCENTAGE },
        columnWidths: [3120, 2000, 4240],
        rows: [
          new TableRow({
            children: [
              createHeaderCell("المخاطرة", 3120),
              createHeaderCell("الاحتمالية", 2000),
              createHeaderCell("الإجراء الوقائي", 4240),
            ]
          }),
          new TableRow({
            children: [
              createCell("تأخر في المتطلبات", 3120),
              createCell("متوسطة", 2000),
              createCell("اجتماعات أسبوعية + توثيق مبكر", 4240),
            ]
          }),
          new TableRow({
            children: [
              createCell("مشاكل في ربط البصمة", 3120, altRowShading),
              createCell("عالية", 2000, altRowShading),
              createCell("الحصول على أجهزة للاختبار مبكراً", 4240, altRowShading),
            ]
          }),
          new TableRow({
            children: [
              createCell("رفض التأمين للمطالبات", 3120),
              createCell("متوسطة", 2000),
              createCell("Scrubber قوي + تدريب المستخدمين", 4240),
            ]
          }),
          new TableRow({
            children: [
              createCell("أداء النظام", 3120, altRowShading),
              createCell("منخفضة", 2000, altRowShading),
              createCell("فهرسة DB + Caching مبكر", 4240, altRowShading),
            ]
          }),
        ]
      }),
      new Paragraph({ spacing: { after: 400 }, children: [] }),

      // Footer
      new Paragraph({
        alignment: AlignmentType.CENTER,
        spacing: { before: 600 },
        children: [new TextRun({ text: "— نهاية الوثيقة —", color: "9CA3AF", italics: true })]
      }),
    ]
  }]
});

Packer.toBuffer(doc).then(buffer => {
  fs.writeFileSync("/home/claude/medical-erp/docs/Implementation_Plan_AR.docx", buffer);
  console.log("Document created successfully!");
});
