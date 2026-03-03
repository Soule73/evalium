<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>{{ __('messages.grade_report') }} - {{ $data['header']['student_name'] }}</title>
  <style>
    @page {
      margin: 0;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif;
      font-size: 10px;
      color: #222;
      line-height: 1.4;
      margin: 0;
      padding: 0;
    }

    /* --- Wrapper (email-template approach) --- */
    .wrapper {
      width: 100%;
      border-collapse: collapse;
    }

    .wrapper-cell {
      padding: 56px 62px 50px 62px;
      vertical-align: top;
    }

    /* --- Spacer rows between sections --- */
    .spacer {
      width: 100%;
      border-collapse: collapse;
    }

    .spacer td {
      height: 12px;
      font-size: 0;
      line-height: 0;
    }

    .spacer-sm td {
      height: 6px;
    }

    /* --- Header --- */
    .header-table {
      width: 100%;
      border-collapse: collapse;
    }

    .header-table td {
      vertical-align: middle;
    }

    .header-logo {
      width: 80px;
      text-align: left;
    }

    .header-logo img {
      max-height: 60px;
      max-width: 80px;
    }

    .header-center {
      text-align: center;
    }

    .school-name {
      font-size: 14px;
      font-weight: 700;
      color: #111;
      text-transform: uppercase;
      letter-spacing: 0.8px;
    }

    .report-title {
      font-size: 11px;
      font-weight: 600;
      color: #333;
      margin-top: 2px;
      text-transform: uppercase;
      letter-spacing: 0.4px;
    }

    .header-period {
      font-size: 9.5px;
      color: #555;
      margin-top: 1px;
    }

    /* --- Separator line --- */
    .separator {
      width: 100%;
      border-collapse: collapse;
    }

    .separator td {
      border-bottom: 1.5px solid #222;
      height: 1px;
      font-size: 0;
      line-height: 0;
      padding: 6px 0;
    }

    /* --- Student Info --- */
    .info-block {
      width: 100%;
      border: 1px solid #000;
      border-collapse: collapse;
    }

    .info-block td {
      padding: 7px 10px;
      font-size: 10px;
      border: 1px solid #000;
    }

    .info-label {
      font-weight: 700;
      color: #333;
      width: 110px;
    }

    .info-value {
      color: #111;
    }

    /* --- Grades Table --- */
    .grades-table {
      width: 100%;
      border-collapse: collapse;
    }

    .grades-table th {
      background-color: #f7f7f7;
      color: #111;
      font-weight: 700;
      font-size: 8.5px;
      padding: 8px 4px;
      text-align: center;
      text-transform: uppercase;
      letter-spacing: 0.3px;
      border: 1px solid #000;
    }

    .grades-table th:first-child {
      text-align: left;
      padding-left: 8px;
    }

    .grades-table td {
      padding: 7px 4px;
      border: 1px solid #000;
      font-size: 9.5px;
      text-align: center;
      vertical-align: middle;
    }

    .grades-table td:first-child {
      text-align: left;
      padding-left: 8px;
    }

    .remark-cell {
      font-size: 8.5px;
      color: #555;
      font-style: italic;
      text-align: left !important;
      padding-left: 6px !important;
    }

    .grade-good {
      font-weight: 700;
    }

    .grade-low {
      font-weight: 600;
      text-decoration: underline;
    }

    /* --- Summary --- */
    .summary-table {
      width: 100%;
      border-collapse: collapse;
    }

    .summary-table th {
      background-color: #f7f7f7;
      color: #111;
      font-weight: 700;
      font-size: 8.5px;
      padding: 7px 8px;
      text-align: left;
      text-transform: uppercase;
      border: 1px solid #000;
    }

    .summary-table td {
      padding: 8px 10px;
      border: 1px solid #000;
      font-size: 10px;
      vertical-align: middle;
    }

    .summary-label {
      font-weight: 600;
      color: #333;
      font-size: 9.5px;
    }

    .summary-value {
      font-weight: 700;
      color: #111;
      font-size: 11px;
    }

    /* --- Remark Box --- */
    .remark-box {
      width: 100%;
      border-collapse: collapse;
    }

    .remark-box th {
      background-color: #f7f7f7;
      font-weight: 700;
      font-size: 8.5px;
      padding: 7px 8px;
      text-align: left;
      text-transform: uppercase;
      border: 1px solid #000;
      color: #111;
    }

    .remark-box td {
      padding: 10px 10px;
      border: 1px solid #000;
      font-size: 10px;
      font-style: italic;
      color: #333;
      height: 40px;
    }

    /* --- Signatures --- */
    .signatures-table {
      width: 100%;
      border-collapse: collapse;
    }

    .signatures-table td {
      width: 50%;
      padding: 5px 10px;
      font-size: 10px;
      vertical-align: top;
    }

    .sig-label {
      font-weight: 700;
      color: #333;
      margin-bottom: 4px;
    }

    .sig-date {
      font-size: 9px;
      color: #666;
    }

    .sig-space {
      height: 50px;
    }

    /* --- Footer --- */
    .footer-table {
      width: 100%;
      border-collapse: collapse;
    }

    .footer-cell {
      text-align: center;
      font-size: 8px;
      color: #999;
      padding-top: 8px;
      border-top: 0.5px solid #ccc;
    }
  </style>
</head>

<body>
  {{-- Outer wrapper table for padding (DomPDF-safe approach) --}}
  <table class="wrapper">
    <tr>
      <td class="wrapper-cell">

        {{-- Header --}}
        <table class="header-table">
          <tr>
            <td class="header-logo">
              @if($logoBase64)
              <img src="{{ $logoBase64 }}" alt="Logo">
              @endif
            </td>
            <td class="header-center">
              <div class="school-name">{{ $data['header']['school_name'] }}</div>
              <div class="report-title">{{ __('messages.grade_report') }}</div>
              <div class="header-period">{{ $data['header']['period'] }} &mdash; {{ $data['header']['academic_year'] }}</div>
            </td>
            <td style="width:55px;"></td>
          </tr>
        </table>

        {{-- Separator line --}}
        <table class="separator">
          <tr>
            <td></td>
          </tr>
        </table>

        {{-- Spacer --}}
        <table class="spacer">
          <tr>
            <td></td>
          </tr>
        </table>

        {{-- Student Info --}}
        <table class="info-block">
          <tr>
            <td class="info-label">{{ __('messages.student') }}</td>
            <td class="info-value"><strong>{{ $data['header']['student_name'] }}</strong></td>
            <td class="info-label">{{ __('messages.class') }}</td>
            <td class="info-value">{{ $data['header']['class_name'] }}</td>
          </tr>
          <tr>
            <td class="info-label">{{ __('messages.level') }}</td>
            <td class="info-value">{{ $data['header']['level_name'] }}</td>
            <td class="info-label">{{ __('messages.academic_year') }}</td>
            <td class="info-value">{{ $data['header']['academic_year'] }}</td>
          </tr>
        </table>

        {{-- Spacer --}}
        <table class="spacer">
          <tr>
            <td></td>
          </tr>
        </table>

        {{-- Grades Table --}}
        <table class="grades-table">
          <thead>
            <tr>
              <th style="width:auto;">{{ __('messages.subject') }}</th>
              <th style="width:45px;">{{ __('messages.coefficient_short') }}</th>
              <th style="width:55px;">{{ __('messages.grade') }} /20</th>
              @if($showClassAverage)
              <th style="width:60px;">{{ __('messages.class_average_short') }}</th>
              @endif
              @if($showMinMax)
              <th style="width:40px;">Min</th>
              <th style="width:40px;">Max</th>
              @endif
              <th style="width:140px;">{{ __('messages.remark') }}</th>
            </tr>
          </thead>
          <tbody>
            @foreach($data['subjects'] as $subject)
            @php
            $subjectRemark = '';
            if (!empty($remarks['subjects'])) {
            foreach ($remarks['subjects'] as $r) {
            if (($r['class_subject_id'] ?? null) === ($subject['class_subject_id'] ?? null)) {
            $subjectRemark = $r['remark'] ?? '';
            break;
            }
            }
            }
            $gradeClass = '';
            if ($subject['grade'] !== null) {
            if ($subject['grade'] >= 14) {
            $gradeClass = 'grade-good';
            } elseif ($subject['grade'] < 10) {
              $gradeClass='grade-low' ;
              }
              }
              @endphp
              <tr>
              <td>{{ $subject['subject_name'] }}</td>
              <td>{{ $subject['coefficient'] }}</td>
              <td class="{{ $gradeClass }}">
                {{ $subject['grade'] !== null ? number_format($subject['grade'], 2) : '-' }}
              </td>
              @if($showClassAverage)
              <td>{{ isset($subject['class_average']) && $subject['class_average'] !== null ? number_format($subject['class_average'], 2) : '-' }}</td>
              @endif
              @if($showMinMax)
              <td>{{ isset($subject['min']) && $subject['min'] !== null ? number_format($subject['min'], 2) : '-' }}</td>
              <td>{{ isset($subject['max']) && $subject['max'] !== null ? number_format($subject['max'], 2) : '-' }}</td>
              @endif
              <td class="remark-cell">{{ $subjectRemark }}</td>
    </tr>
    @endforeach
    </tbody>
  </table>

  {{-- Spacer --}}
  <table class="spacer">
    <tr>
      <td></td>
    </tr>
  </table>

  {{-- Summary --}}
  @php
  $avg = $data['footer']['average'];
  $avgFormatted = $avg !== null ? number_format($avg, 2) . ' / 20' : '-';
  @endphp
  <table class="summary-table">
    <thead>
      <tr>
        <th colspan="4">{{ __('messages.summary') }}</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td style="width:25%;">
          <span class="summary-label">{{ __('messages.general_average') }}</span>
        </td>
        <td style="width:25%;">
          <span class="summary-value">{{ $avgFormatted }}</span>
        </td>
        <td style="width:25%;">
          <span class="summary-label">{{ __('messages.total_coefficient') }}</span>
        </td>
        <td style="width:25%;">
          <span class="summary-value">{{ $data['footer']['total_coefficient'] }}</span>
        </td>
      </tr>
      @if($showRanking && $data['footer']['rank'] !== null)
      <tr>
        <td>
          <span class="summary-label">{{ __('messages.rank') }}</span>
        </td>
        <td>
          <span class="summary-value">{{ $data['footer']['rank'] }} / {{ $data['footer']['class_size'] }}</span>
        </td>
        <td>
          <span class="summary-label">{{ __('messages.class_size') }}</span>
        </td>
        <td>
          <span class="summary-value">{{ $data['footer']['class_size'] }}</span>
        </td>
      </tr>
      @endif
    </tbody>
  </table>

  {{-- Spacer --}}
  <table class="spacer">
    <tr>
      <td></td>
    </tr>
  </table>

  {{-- General Remark --}}
  <table class="remark-box">
    <thead>
      <tr>
        <th>{{ __('messages.general_remark') }}</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>{{ $generalRemark ?: '-' }}</td>
      </tr>
    </tbody>
  </table>

  {{-- Spacer (larger before signatures) --}}
  <table class="spacer">
    <tr>
      <td style="height:20px;"></td>
    </tr>
  </table>

  {{-- Signatures --}}
  <table class="signatures-table">
    <tr>
      <td>
        <div class="sig-label">{{ __('messages.administration') }}</div>
        @if($report->validated_at)
        <div class="sig-date">
          {{ __('messages.validated_on') }} {{ $report->validated_at->format('d/m/Y') }}
          @if($report->validator)
          - {{ $report->validator->name }}
          @endif
        </div>
        @endif
        <div class="sig-space"></div>
      </td>
      <td>
        <div class="sig-label">{{ __('messages.parent_signature') }}</div>
        <div class="sig-space"></div>
      </td>
    </tr>
  </table>

  {{-- Spacer --}}
  <table class="spacer spacer-sm">
    <tr>
      <td></td>
    </tr>
  </table>

  {{-- Footer --}}
  <table class="footer-table">
    <tr>
      <td class="footer-cell">
        {{ $data['header']['school_name'] }} | {{ __('messages.grade_report') }}
        | {{ __('messages.generated_on') }} {{ now()->format('d/m/Y H:i') }}
      </td>
    </tr>
  </table>

  </td>
  </tr>
  </table>
</body>

</html>