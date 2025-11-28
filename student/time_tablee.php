<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NIT Timetable - B.Tech First Year</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            animation: fadeIn 0.8s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .header p {
            font-size: 14px;
            opacity: 0.9;
        }

        .timetable-wrapper {
            overflow-x: auto;
            padding: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        th {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 15px 8px;
            font-weight: 600;
            text-align: center;
            border: 1px solid #ddd;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        td {
            padding: 12px 8px;
            border: 1px solid #ddd;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        td::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s ease;
        }

        td:hover::before {
            left: 100%;
        }

        td:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
            z-index: 5;
            cursor: pointer;
        }

        .day-cell {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: bold;
            font-size: 14px;
        }

        .room-cell {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            font-weight: bold;
        }

        .subject-cell {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            font-weight: 600;
        }

        .time-slot {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            font-weight: bold;
            color: #333;
        }

        .class-cell {
            background: #f8f9fa;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .class-cell:hover {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            transform: scale(1.08) translateY(-2px);
        }

        .practical {
            background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
        }

        .practical:hover {
            background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
        }

        .library {
            background: linear-gradient(135deg, #a1c4fd 0%, #c2e9fb 100%);
        }

        .library:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .break-cell {
            background: linear-gradient(135deg, #ffeaa7 0%, #fdcb6e 100%);
            font-weight: bold;
            color: #2d3436;
        }

        .break-cell:hover {
            background: linear-gradient(135deg, #fdcb6e 0%, #e17055 100%);
            color: white;
        }

        .faculty-cell {
            background: linear-gradient(135deg, #d4fc79 0%, #96e6a1 100%);
            font-size: 11px;
        }

        .faculty-cell:hover {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
        }

        .legend {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 15px;
            padding: 20px;
            background: #f8f9fa;
            margin: 20px;
            border-radius: 10px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 15px;
            background: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .legend-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .legend-color {
            width: 30px;
            height: 20px;
            border-radius: 3px;
        }

        .footer {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            color: #666;
            font-size: 12px;
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 20px;
            }
            
            table {
                font-size: 10px;
            }
            
            td, th {
                padding: 8px 4px;
            }
        }

        .tooltip {
            position: absolute;
            background: rgba(0,0,0,0.9);
            color: white;
            padding: 10px;
            border-radius: 5px;
            font-size: 12px;
            z-index: 1000;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.3s ease;
            white-space: nowrap;
        }

        .tooltip.show {
            opacity: 1;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>NAGPUR INSTITUTE OF TECHNOLOGY</h1>
            <p>DEPARTMENT OF B. TECH. FIRST YEAR - FIRST SEMESTER SESSION 2025-26</p>
            <p>W.E.F. 29/09/2025</p>
        </div>

        <div class="timetable-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>DAY</th>
                        <th>ROOM NO.</th>
                        <th>SEC</th>
                        <th>9:30-10:30</th>
                        <th>10:30-11:30</th>
                        <th>11:30-12:00</th>
                        <th>12:00-1:00</th>
                        <th>1:00-2:00</th>
                        <th>2:00-2:30</th>
                        <th>2:30-3:30</th>
                        <th>3:30-4:15</th>
                        <th>SEC</th>
                        <th>SUB. NAME</th>
                        <th>ABR</th>
                        <th>FACULTY NAME</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- MONDAY -->
                    <tr>
                        <td rowspan="6" class="day-cell">MON</td>
                        <td class="room-cell">B-104</td>
                        <td>ACSE</td>
                        <td colspan="2" class="practical">E.CHEM(B1)/ BEE(B2) PRACTICAL</td>
                        <td class="break-cell">BREAK</td>
                        <td class="class-cell">A. MATH-I (MD)</td>
                        <td class="class-cell">PSC(AS)</td>
                        <td class="break-cell">LUNCH</td>
                        <td colspan="2" class="practical">T&P SESSION<br>PSC(B1,B2) PRACTICAL</td>
                        <td>A</td>
                        <td>A.MATH-I</td>
                        <td>MD</td>
                        <td class="faculty-cell">MRS. MONA DANGE</td>
                    </tr>
                    <tr>
                        <td class="room-cell">B-105</td>
                        <td>BCSE</td>
                        <td class="class-cell">PSC(AS)</td>
                        <td class="class-cell">A. MATH-I (MD)</td>
                        <td class="break-cell">BREAK</td>
                        <td class="class-cell">E.CHEM(SK)</td>
                        <td class="class-cell">BEE (RK)</td>
                        <td class="break-cell">LUNCH</td>
                        <td colspan="2" class="practical">CS (B1)/CC (B2) PRACTICAL</td>
                        <td>CSE</td>
                        <td>E.CHEM</td>
                        <td>SRK</td>
                        <td class="faculty-cell">DR. SONIKA KOCHHAR</td>
                    </tr>
                    <tr>
                        <td class="room-cell">B-106</td>
                        <td>IT</td>
                        <td class="class-cell">E.CHEM(MJ)</td>
                        <td class="class-cell">A. MATH-I (VR)</td>
                        <td class="break-cell">BREAK</td>
                        <td class="class-cell">PSC(AS)</td>
                        <td class="class-cell">CS(HC)</td>
                        <td class="break-cell">LUNCH</td>
                        <td colspan="2" class="practical">CS (B1)/CC (B2) PRACTICAL</td>
                        <td></td>
                        <td>CS</td>
                        <td>HC</td>
                        <td class="faculty-cell">MRS. HITASHI CHAUHAN</td>
                    </tr>
                    <tr>
                        <td class="room-cell">B-107</td>
                        <td>EE</td>
                        <td class="class-cell">A.MATH-I(PD)</td>
                        <td class="class-cell">A.PHY(JB)</td>
                        <td class="break-cell">BREAK</td>
                        <td class="class-cell">CS(MS)</td>
                        <td class="class-cell">BEE (RD)</td>
                        <td class="break-cell">LUNCH</td>
                        <td colspan="2" class="practical">A.PHY(B1)/EG (B2) PRACTICAL</td>
                        <td></td>
                        <td>BEE</td>
                        <td>RD</td>
                        <td class="faculty-cell">MRS. RACHANA DAGA</td>
                    </tr>
                    <tr>
                        <td class="room-cell">B-206</td>
                        <td>ME</td>
                        <td class="class-cell">CP (AS)</td>
                        <td class="class-cell">A.PHY(DM)</td>
                        <td class="break-cell">BREAK</td>
                        <td class="class-cell">A.MATH-I(PD)</td>
                        <td class="class-cell">EG(SK)</td>
                        <td class="break-cell">LUNCH</td>
                        <td colspan="2" class="practical">EG (B1,B2) PRACTICAL</td>
                        <td></td>
                        <td>PSC</td>
                        <td>AS</td>
                        <td class="faculty-cell">MR. AYAZ SHAIKH</td>
                    </tr>
                    <tr>
                        <td class="room-cell">B-207</td>
                        <td>CE</td>
                        <td colspan="2" class="practical">CS (B1)/CC (B2) PRACTICAL</td>
                        <td class="break-cell">BREAK</td>
                        <td class="class-cell">A. MATH-I (VR)</td>
                        <td class="class-cell">EG(GK)</td>
                        <td class="break-cell">LUNCH</td>
                        <td class="class-cell">FOV(AK)</td>
                        <td class="library">TGM.LIBRARY</td>
                        <td></td>
                        <td>WT</td>
                        <td>PB</td>
                        <td class="faculty-cell">PURNIMA BHUYAR</td>
                    </tr>

                    <!-- TUESDAY -->
                    <tr>
                        <td rowspan="6" class="day-cell">TUE</td>
                        <td class="room-cell">B-104</td>
                        <td>ACSE</td>
                        <td colspan="2" class="practical">E.CHEM(B1)/ BEE(B1) PRACTICAL</td>
                        <td class="break-cell">BREAK</td>
                        <td class="class-cell">PSC(AS)</td>
                        <td class="class-cell">CS(HC)</td>
                        <td class="break-cell">LUNCH</td>
                        <td class="class-cell">A. MATH-I (MD)</td>
                        <td class="library">TGM.LIBRARY</td>
                        <td>B</td>
                        <td>A.MATH-I</td>
                        <td>MD</td>
                        <td class="faculty-cell">MRS. MONA DANGE</td>
                    </tr>
                    <tr>
                        <td class="room-cell">B-105</td>
                        <td>BCSE</td>
                        <td class="class-cell">PSC(AS)</td>
                        <td class="class-cell">A. MATH-I (MD)</td>
                        <td class="break-cell">BREAK</td>
                        <td class="class-cell">BEE (RK)</td>
                        <td class="class-cell">E.CHEM(SK)</td>
                        <td class="break-cell">LUNCH</td>
                        <td colspan="2" class="practical">CS (B1)/CC (B2) PRACTICAL</td>
                        <td>CSE</td>
                        <td>E.CHEM</td>
                        <td>SRK</td>
                        <td class="faculty-cell">DR. SONIKA KOCHHAR</td>
                    </tr>
                    <tr>
                        <td class="room-cell">B-106</td>
                        <td>IT</td>
                        <td class="class-cell">E.CHEM(MJ)</td>
                        <td class="class-cell">A. MATH-I (VR)</td>
                        <td class="break-cell">BREAK</td>
                        <td class="class-cell">BEE (TS)</td>
                        <td class="class-cell">PSC(AS)</td>
                        <td class="break-cell">LUNCH</td>
                        <td colspan="2" class="practical">PSC(B1,B2) PRACTICAL</td>
                        <td></td>
                        <td>CS</td>
                        <td>HC</td>
                        <td class="faculty-cell">MRS. HITASHI CHAUHAN</td>
                    </tr>
                    <tr>
                        <td class="room-cell">B-107</td>
                        <td>EE</td>
                        <td class="class-cell">A.MATH-I(PD)</td>
                        <td class="class-cell">A.PHY(JB)</td>
                        <td class="break-cell">BREAK</td>
                        <td class="class-cell">BEE (RD)</td>
                        <td class="class-cell">EG(RD)</td>
                        <td class="break-cell">LUNCH</td>
                        <td colspan="2" class="practical">A.PHY(B2)/EG (B1) PRACTICAL</td>
                        <td></td>
                        <td>BEE</td>
                        <td>RK</td>
                        <td class="faculty-cell">MR. RAHUL KADAM</td>
                    </tr>
                    <tr>
                        <td class="room-cell">B-206</td>
                        <td>ME</td>
                        <td class="class-cell">CP (AS)</td>
                        <td class="class-cell">A.PHY(DM)</td>
                        <td class="break-cell">BREAK</td>
                        <td class="class-cell">A.MATH-I(PD)</td>
                        <td class="class-cell">EG(SK)</td>
                        <td class="break-cell">LUNCH</td>
                        <td colspan="2" class="practical">WS(B1,B2) PRACTICAL</td>
                        <td></td>
                        <td>PSC</td>
                        <td>AS</td>
                        <td class="faculty-cell">MR. AYAZ SHAIKH</td>
                    </tr>
                    <tr>
                        <td class="room-cell">B-207</td>
                        <td>CE</td>
                        <td colspan="2" class="practical">CS (B1)/CC (B2) PRACTICAL</td>
                        <td class="break-cell">BREAK</td>
                        <td class="class-cell">A.PHY(DM)</td>
                        <td class="class-cell">CS(MS)</td>
                        <td class="break-cell">LUNCH</td>
                        <td colspan="2" class="subject-cell">T&P SESSION</td>
                        <td></td>
                        <td>WT</td>
                        <td>PB</td>
                        <td class="faculty-cell">PURNIMA BHUYAR</td>
                    </tr>

                    <!-- WEDNESDAY -->
                    <tr>
                        <td rowspan="6" class="day-cell">WED</td>
                        <td class="room-cell">B-104</td>
                        <td>ACSE</td>
                        <td class="class-cell">PSC(AS)</td>
                        <td class="class-cell">A. MATH-I (MD)</td>
                        <td class="break-cell">BREAK</td>
                        <td class="class-cell">E.CHEM(SK)</td>
                        <td class="class-cell">BEE (RD)</td>
                        <td class="break-cell">LUNCH</td>
                        <td colspan="2" class="practical">PSC(B1,B2) PRACTICAL</td>
                        <td></td>
                        <td>A.MATH-I</td>
                        <td>VR</td>
                        <td class="faculty-cell">MRS.VIDYA RAUT</td>
                    </tr>
                    <tr>
                        <td class="room-cell">B-105</td>
                        <td>BCSE</td>
                        <td class="class-cell">CS(HC)</td>
                        <td class="class-cell">E.CHEM(SK)</td>
                        <td class="break-cell">BREAK</td>
                        <td class="class-cell">A. MATH-I (MD)</td>
                        <td class="class-cell">PSC(AS)</td>
                        <td class="break-cell">LUNCH</td>
                        <td colspan="2" class="practical">CS (B2)/CC (B1) PRACTICAL</td>
                        <td></td>
                        <td>E.CHEM</td>
                        <td>MJ</td>
                        <td class="faculty-cell">DR. MEGHNA JUMBHLE</td>
                    </tr>
                    <tr>
                        <td class="room-cell">B-106</td>
                        <td>IT</td>
                        <td colspan="2" class="practical">E.CHEM(B1)/ BEE(B2) PRACTICAL</td>
                        <td class="break-cell">BREAK</td>
                        <td class="class-cell">A. MATH-I (VR)</td>
                        <td class="class-cell">BEE(TS)</td>
                        <td class="break-cell">LUNCH</td>
                        <td colspan="2" class="subject-cell">T&P SESSION</td>
                        <td>IT</td>
                        <td>CS</td>
                        <td>HC</td>
                        <td class="faculty-cell">MRS. HITASHI CHAUHAN</td>
                    </tr>
                    <tr>
                        <td class="room-cell">B-107</td>
                        <td>EE</td>
                        <td colspan="2" class="practical">CS (B1)/CC (B2) PRACTICAL</td>
                        <td class="break-cell">BREAK</td>
                        <td class="class-cell">A.PHY(JB)</td>
                        <td class="class-cell">EG(RD)</td>
                        <td class="break-cell">LUNCH</td>
                        <td class="class-cell">A. MATH-I (PD)</td>
                        <td class="library">TGM.LIBRARY</td>
                        <td></td>
                        <td>BEE</td>
                        <td>TS</td>
                        <td class="faculty-cell">MR. TUSHAR SHELKE</td>
                    </tr>
                    <tr>
                        <td class="room-cell">B-206</td>
                        <td>ME</td>
                        <td class="class-cell">A.MATH-I(PD)</td>
                        <td class="class-cell">A.PHY(DM)</td>
                        <td class="break-cell">BREAK</td>
                        <td class="class-cell">CP(AS)</td>
                        <td class="class-cell">CS(MS)</td>
                        <td class="break-cell">LUNCH</td>
                        <td colspan="2" class="practical">A.PHY(B1)/CC (B2) PRACTICAL</td>
                        <td></td>
                        <td>PSC</td>
                        <td>AS</td>
                        <td class="faculty-cell">MISS. AYUSHI SHARMA</td>
                    </tr>
                    <tr>
                        <td class="room-cell">B-207</td>
                        <td>CE</td>
                        <td class="class-cell">EG(GK)</td>
                        <td class="class-cell">A. MATH-I (VR)</td>
                        <td class="break-cell">RECESS</td>
                        <td class="class-cell">A.PHY(DM)</td>
                        <td class="class-cell">FOV(AK)</td>
                        <td class="break-cell">RECESS</td>
                        <td colspan="2" class="practical">WS(B1)/ FOV(B2) PRACTICAL</td>
                        <td></td>
                        <td>WT</td>
                        <td>PB</td>
                        <td class="faculty-cell">PURNIMA BHUYAR</td>
                    </tr>

                    <!-- THURSDAY -->
                    <tr>
                        <td rowspan="6" class="day-cell">THU</td>
                        <td class="room-cell">B-104</td>
                        <td>ACSE</td>
                        <td class="class-cell">A. MATH-I (MD)</td>
                        <td class="class-cell">PSC(AS)</td>
                        <td class="break-cell">RECESS</td>
                        <td class="class-cell">BEE (RD)</td>
                        <td class="class-cell">E.CHEM(SK)</td>
                        <td class="break-cell">RECESS</td>
                        <td colspan="2" class="practical">WT(B1,B2) PRACTICAL</td>
                        <td></td>
                        <td>A.MATH-I</td>
                        <td>PD</td>
                        <td class="faculty-cell">MR. PRASHANT DANGE</td>
                    </tr>
                    <tr>
                        <td class="room-cell">B-105</td>
                        <td>BCSE</td>
                        <td colspan="2" class="practical">E.CHEM(B1)/ BEE(B2) PRACTICAL</td>
                        <td class="break-cell">RECESS</td>
                        <td class="class-cell">PSC(AS)</td>
                        <td class="class-cell">BEE (RK)</td>
                        <td class="break-cell">RECESS</td>
                        <td class="class-cell">A. MATH-I (MD)</td>
                        <td class="library">TGM.LIBRARY</td>
                        <td></td>
                        <td>A.PHY</td>
                        <td>JB</td>
                        <td class="faculty-cell">DR. JITENDRA BHAISWAR</td>
                    </tr>
                    <tr>
                        <td class="room-cell">B-106</td>
                        <td>IT</td>
                        <td class="class-cell">E.CHEM(MJ)</td>
                        <td class="class-cell">PSC(AS)</td>
                        <td class="break-cell">RECESS</td>
                        <td class="class-cell">A. MATH-I (VR)</td>
                        <td class="class-cell">BEE(TS)</td>
                        <td class="break-cell">RECESS</td>
                        <td colspan="2" class="practical">CS (B2)/CC (B1) PRACTICAL</td>
                        <td>EE</td>
                        <td>CS</td>
                        <td>MS</td>
                        <td class="faculty-cell">DR. MOHAMMAD SABIR</td>
                    </tr>
                    <tr>
                        <td class="room-cell">B-107</td>
                        <td>EE</td>
                        <td colspan="2" class="practical">CS (B2)/CC (B1) PRACTICAL</td>
                        <td class="break-cell">RECESS</td>
                        <td class="class-cell">A.MATH-I(PD)</td>
                        <td class="class-cell">CS(MS)</td>
                        <td class="break-cell">RECESS</td>
                        <td colspan="2" class="subject-cell">T&P SESSION</td>
                        <td></td>
                        <td>BEE</td>
                        <td>RD</td>
                        <td class="faculty-cell">MRS. RACHANA DAGA</td>
                    </tr>
                    <tr>
                        <td class="room-cell">B-206</td>
                        <td>ME</td>
                        <td class="class-cell">A.MATH-I(PD)</td>
                        <td class="class-cell">A.PHY(DM)</td>
                        <td class="break-cell">RECESS</td>
                        <td class="class-cell">EG(SK)</td>
                        <td class="class-cell">CP (AS)</td>
                        <td class="break-cell">RECESS</td>
                        <td colspan="2" class="practical">A.PHY(B2)/CC (B1) PRACTICAL</td>
                        <td></td>
                        <td>EG</td>
                        <td>RD</td>
                        <td class="faculty-cell">MR. ROHAN DESHMUKH</td>
                    </tr>
                    <tr>
                        <td class="room-cell">B-207</td>
                        <td>CE</td>
                        <td class="class-cell">FOV(AK)</td>
                        <td class="class-cell">A. MATH-I (VR)</td>
                        <td class="break-cell">RECESS</td>
                        <td class="class-cell">A.PHY(DM)</td>
                        <td class="class-cell">EG(GK)</td>
                        <td class="break-cell">RECESS</td>
                        <td colspan="2" class="practical">WS(B2)/ FOV(B1) PRACTICAL</td>
                        <td></td>
                        <td>SPI</td>
                        <td>HG</td>
                        <td class="faculty-cell">MR. HARSHAL GHATODE</td>
                    </tr>

                    <!-- FRIDAY -->
                    <tr>
                        <td rowspan="6" class="day-cell">FRI</td>
                        <td class="room-cell">B-104</td>
                        <td>ACSE</td>
                        <td class="class-cell">PSC(AS)</td>
                        <td class="class-cell">E.CHEM(SK)</td>
                        <td class="break-cell">RECESS</td>
                        <td class="class-cell">A. MATH-I (MD)</td>
                        <td class="class-cell">BEE (RD)</td>
                        <td class="break-cell">RECESS</td>
                        <td colspan="2" class="practical">CS (B1)/CC (B2) PRACTICAL</td>
                        <td></td>
                        <td>A.MATH-I</td>
                        <td>PD</td>
                        <td class="faculty-cell">MR. PRASHANT DANGE</td>
                    </tr>
                    <tr>
                        <td class="room-cell">B-105</td>
                        <td>BCSE</td>
                        <td class="class-cell">A. MATH-I (MD)</td>
                        <td class="class-cell">CS(HC)</td>
                        <td class="break-cell">RECESS</td>
                        <td class="class-cell">BEE (RK)</td>
                        <td class="class-cell">E.CHEM(SK)</td>
                        <td class="break-cell">RECESS</td>
                        <td colspan="2" class="practical">WT(B1,B2) PRACTICAL</td>
                        <td></td>
                        <td>A.PHY</td>
                        <td>DM</td>
                        <td class="faculty-cell">MR. DHIRAJ MEGHE</td>
                    </tr>
                    <tr>
                        <td class="room-cell">B-106</td>
                        <td>IT</td>
                        <td colspan="2" class="practical">E.CHEM(B1)/ BEE(B1) PRACTICAL</td>
                        <td class="break-cell">RECESS</td>
                        <td class="class-cell">E.CHEM(MJ)</td>
                        <td class="class-cell">BEE (TS)</td>
                        <td class="break-cell">RECESS</td>
                        <td class="class-cell">A. MATH-I (VR)</td>
                        <td class="library">TGM.LIBRARY</td>
                        <td></td>
                        <td>CS</td>
                        <td>MS</td>
                        <td class="faculty-cell">DR. MOHAMMAD SABIR</td>
                    </tr>
                    <tr>
                        <td class="room-cell">B-107</td>
                        <td>EE</td>
                        <td class="class-cell">A.MATH-I(PD)</td>
                        <td class="class-cell">BEE (RD)</td>
                        <td class="break-cell">RECESS</td>
                        <td class="class-cell">A.PHY(JB)</td>
                        <td class="class-cell">EG(RD)</td>
                        <td class="break-cell">RECESS</td>
                        <td colspan="2" class="practical">BEE(B1)/ SPI(B2) PRACTICAL</td>
                        <td>ME</td>
                        <td>EG</td>
                        <td>SK</td>
                        <td class="faculty-cell">MR. SAMRAT KAVISHWAR</td>
                    </tr>
                    <tr>
                        <td class="room-cell">B-206</td>
                        <td>ME</td>
                        <td class="class-cell">CP (B1,B2) PRACTICAL</td>
                        <td></td>
                        <td class="break-cell">RECESS</td>
                        <td class="class-cell">A.PHY(DM)</td>
                        <td class="class-cell">EG(SK)</td>
                        <td class="break-cell">RECESS</td>
                        <td colspan="2" class="subject-cell">T&P SESSION</td>
                        <td></td>
                        <td>CP</td>
                        <td>AS</td>
                        <td class="faculty-cell">MISS. AYUSHI SHARMA</td>
                    </tr>
                    <tr>
                        <td class="room-cell">B-207</td>
                        <td>CE</td>
                        <td class="class-cell">EG(GK)</td>
                        <td class="class-cell">A.PHY(DM)</td>
                        <td class="break-cell">RECESS</td>
                        <td class="class-cell">A. MATH-I (VR)</td>
                        <td class="class-cell">FOV(AK)</td>
                        <td class="break-cell">RECESS</td>
                        <td colspan="2" class="practical">A.PHY(B1)/EG (B2) PRACTICAL</td>
                        <td></td>
                        <td>WS</td>
                        <td>SK</td>
                        <td class="faculty-cell">MR. SAMRAT KAVISHWAR</td>
                    </tr>

                    <!-- SATURDAY -->
                    <tr>
                        <td rowspan="6" class="day-cell">SAT</td>
                        <td class="room-cell">B-104</td>
                        <td>ACSE</td>
                        <td class="class-cell">E.CHEM(SK)</td>
                        <td class="class-cell">A. MATH-I (MD)</td>
                        <td class="break-cell">RECESS</td>
                        <td class="class-cell">BEE (RD)</td>
                        <td class="class-cell">CS(HC)</td>
                        <td class="break-cell">RECESS</td>
                        <td colspan="2" class="practical">CS (B2)/CC (B1) PRACTICAL</td>
                        <td></td>
                        <td>A.MATH-I</td>
                        <td>VR</td>
                        <td class="faculty-cell">MRS.VIDYA RAUT</td>
                    </tr>
                    <tr>
                        <td class="room-cell">B-105</td>
                        <td>BCSE</td>
                        <td colspan="2" class="practical">E.CHEM(B2)/ BEE(B1) PRACTICAL</td>
                        <td class="break-cell">RECESS</td>
                        <td class="class-cell">A. MATH-I (MD)</td>
                        <td class="class-cell">E.CHEM(SK)</td>
                        <td class="break-cell">RECESS</td>
                        <td colspan="2" class="subject-cell">T&P SESSION</td>
                        <td></td>
                        <td>A.PHY</td>
                        <td>DM</td>
                        <td class="faculty-cell">MR. DHIRAJ MEGHE</td>
                    </tr>
                    <tr>
                        <td class="room-cell">B-106</td>
                        <td>IT</td>
                        <td class="class-cell">CS(HC)</td>
                        <td class="class-cell">A. MATH-I (VR)</td>
                        <td class="break-cell">RECESS</td>
                        <td class="class-cell">E.CHEM(MJ)</td>
                        <td class="class-cell">PSC(AS)</td>
                        <td class="break-cell">RECESS</td>
                        <td colspan="2" class="practical">WT(B1,B2) PRACTICAL</td>
                        <td>CE</td>
                        <td>CS</td>
                        <td>MS</td>
                        <td class="faculty-cell">DR. MOHAMMAD SABIR</td>
                    </tr>
                    <tr>
                        <td class="room-cell">B-107</td>
                        <td>EE</td>
                        <td class="class-cell">BEE (RD)</td>
                        <td class="class-cell">A.MATH-I(PD)</td>
                        <td class="break-cell">RECESS</td>
                        <td class="class-cell">A.PHY(JB)</td>
                        <td class="class-cell">EG(RD)</td>
                        <td class="break-cell">RECESS</td>
                        <td colspan="2" class="practical">BEE(B2)/ SPI(B1) PRACTICAL</td>
                        <td></td>
                        <td>EG</td>
                        <td>GK</td>
                        <td class="faculty-cell">MR. GIRISAN KHAN</td>
                    </tr>
                    <tr>
                        <td class="room-cell">B-206</td>
                        <td>ME</td>
                        <td colspan="2" class="practical">CS (B1,B2) PRACTICAL</td>
                        <td class="break-cell">RECESS</td>
                        <td class="class-cell">A.MATH-I(PD)</td>
                        <td class="class-cell">EG(SK)</td>
                        <td class="break-cell">RECESS</td>
                        <td class="class-cell">CS(MS)</td>
                        <td class="library">TGM.LIBRARY</td>
                        <td></td>
                        <td>FOV</td>
                        <td>AK</td>
                        <td class="faculty-cell">MR. AMIT KHARWADE</td>
                    </tr>
                    <tr>
                        <td class="room-cell">B-207</td>
                        <td>CE</td>
                        <td class="class-cell">A. MATH-I (VR)</td>
                        <td class="class-cell">A.PHY(DM)</td>
                        <td class="break-cell">RECESS</td>
                        <td class="class-cell">CS(MS)</td>
                        <td class="class-cell">EG(GK)</td>
                        <td class="break-cell">RECESS</td>
                        <td colspan="2" class="practical">A.PHY(B2)/EG (B1) PRACTICAL</td>
                        <td></td>
                        <td>WS</td>
                        <td>AG</td>
                        <td class="faculty-cell">MR. ABDUL GAFFAR</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="legend">
            <div class="legend-item">
                <div class="legend-color" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);"></div>
                <span>Theory Classes</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);"></div>
                <span>Practical Sessions</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: linear-gradient(135deg, #a1c4fd 0%, #c2e9fb 100%);"></div>
                <span>Library</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: linear-gradient(135deg, #ffeaa7 0%, #fdcb6e 100%);"></div>
                <span>Break Time</span>
            </div>
        </div>

            <!-- Action Buttons -->
       <div style="text-align:center; margin:40px 0;">

    <a href="index.php"
       style="
        padding:12px 28px;
        background:#007bff;
        color:#fff;
        text-decoration:none;
        border-radius:8px;
        font-size:18px;
        margin-right:15px;
        display:inline-block;
        transition:all 0.3s ease;
       "
       onmouseover="this.style.background='#0056b3'; this.style.boxShadow='0 4px 10px rgba(0,0,0,0.3)'"
       onmouseout="this.style.background='#007bff'; this.style.boxShadow='none'">
      Back Dashboard
    </a>

    

</div>
    </div>

    <div class="tooltip" id="tooltip"></div>
    <br>
   <!-- Compact Footer -->
    <div style="background: linear-gradient(135deg, #0a0e27 0%, #1a1f3a 50%, #2a3254 100%); position: relative; overflow: hidden;">
        
        <!-- Animated Top Border -->
        <div style="height: 2px; background: linear-gradient(90deg, #4a9eff, #00d4ff, #4a9eff, #00d4ff); background-size: 200% 100%;"></div>
        
        <!-- Main Footer Container -->
        <div style="max-width: 1000px; margin: 0 auto; padding: 30px 20px 20px;">
            
            <!-- Developer Section -->
            <div style="background: rgba(255, 255, 255, 0.03); padding: 20px 20px; border-radius: 15px; border: 1px solid rgba(74, 158, 255, 0.15); text-align: center; box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);">
                
                <!-- Title -->
                <p style="color: #ffffff; font-size: 14px; margin: 0 0 12px; font-weight: 500; letter-spacing: 0.5px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">✨ Designed & Developed by</p>
                
                <!-- Company Link -->
                <a href="https://himanshufullstackdeveloper.github.io/techyugsoftware/" style="display: inline-block; color: #ffffff; font-size: 16px; font-weight: 700; text-decoration: none; padding: 8px 24px; border: 2px solid #4a9eff; border-radius: 30px; background: linear-gradient(135deg, rgba(74, 158, 255, 0.2), rgba(0, 212, 255, 0.2)); box-shadow: 0 3px 12px rgba(74, 158, 255, 0.3); margin-bottom: 15px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
                    🚀 Techyug Software Pvt. Ltd.
                </a>
                
                <!-- Divider -->
                <div style="width: 50%; height: 1px; background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent); margin: 15px auto;"></div>
                
                <!-- Team Label -->
                <p style="color: #888; font-size: 10px; margin: 0 0 12px; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 600; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">💼 Development Team</p>
                
                <!-- Developer Badges -->
                <div style="display: flex; justify-content: center; gap: 12px; flex-wrap: wrap; margin-top: 12px;">
                    
                    <!-- Developer 1 -->
                    <a href="https://himanshufullstackdeveloper.github.io/portfoilohimanshu/" style="color: #ffffff; font-size: 13px; text-decoration: none; padding: 8px 16px; background: linear-gradient(135deg, rgba(74, 158, 255, 0.25), rgba(0, 212, 255, 0.25)); border-radius: 20px; border: 1px solid rgba(74, 158, 255, 0.4); display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 3px 10px rgba(74, 158, 255, 0.2); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
                        <span style="font-size: 16px;">👨‍💻</span>
                        <span style="font-weight: 600;">Himanshu Patil</span>
                    </a>
                    
                    <!-- Developer 2 -->
                    <a href="https://devpranaypanore.github.io/Pranaypanore-live-.html/" style="color: #ffffff; font-size: 13px; text-decoration: none; padding: 8px 16px; background: linear-gradient(135deg, rgba(74, 158, 255, 0.25), rgba(0, 212, 255, 0.25)); border-radius: 20px; border: 1px solid rgba(74, 158, 255, 0.4); display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 3px 10px rgba(74, 158, 255, 0.2); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
                        <span style="font-size: 16px;">👨‍💻</span>
                        <span style="font-weight: 600;">Pranay Panore</span>
                    </a>
                </div>
                
                <!-- Role Tags -->
                <div style="margin-top: 15px; display: flex; justify-content: center; gap: 10px; flex-wrap: wrap;">
                    <span style="color: #4a9eff; font-size: 10px; padding: 4px 12px; background: rgba(74, 158, 255, 0.1); border-radius: 12px; border: 1px solid rgba(74, 158, 255, 0.3); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">Full Stack</span>
                    <span style="color: #00d4ff; font-size: 10px; padding: 4px 12px; background: rgba(0, 212, 255, 0.1); border-radius: 12px; border: 1px solid rgba(0, 212, 255, 0.3); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">UI/UX</span>
                    <span style="color: #4a9eff; font-size: 10px; padding: 4px 12px; background: rgba(74, 158, 255, 0.1); border-radius: 12px; border: 1px solid rgba(74, 158, 255, 0.3); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">Database</span>
                </div>
            </div>
            
            <!-- Bottom Section -->
            <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid rgba(255, 255, 255, 0.1); text-align: center;">
                
                <!-- Copyright -->
                <p style="color: #888; font-size: 12px; margin: 0 0 10px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">© 2025 NIT AMMS. All rights reserved.</p>
                
                <!-- Made With Love -->
                <p style="color: #666; font-size: 11px; margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
                    Made with <span style="color: #ff4757; font-size: 14px;">❤️</span> by Techyug Software
                </p>
                
                <!-- Social Links -->
                <div style="margin-top: 15px; display: flex; justify-content: center; gap: 10px;">
                    <a href="#" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; background: rgba(74, 158, 255, 0.1); border: 1px solid rgba(74, 158, 255, 0.3); border-radius: 50%; color: #4a9eff; text-decoration: none; font-size: 14px;">📧</a>
                    <a href="#" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; background: rgba(74, 158, 255, 0.1); border: 1px solid rgba(74, 158, 255, 0.3); border-radius: 50%; color: #4a9eff; text-decoration: none; font-size: 14px;">🌐</a>
                    <a href="#" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; background: rgba(74, 158, 255, 0.1); border: 1px solid rgba(74, 158, 255, 0.3); border-radius: 50%; color: #4a9eff; text-decoration: none; font-size: 14px;">💼</a>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Add hover tooltips
        const cells = document.querySelectorAll('td:not(.day-cell):not(.room-cell):not(.break-cell)');
        const tooltip = document.getElementById('tooltip');

        cells.forEach(cell => {
            cell.addEventListener('mouseenter', (e) => {
                if (cell.textContent.trim()) {
                    tooltip.textContent = cell.textContent;
                    tooltip.classList.add('show');
                    updateTooltipPosition(e);
                }
            });

            cell.addEventListener('mousemove', updateTooltipPosition);

            cell.addEventListener('mouseleave', () => {
                tooltip.classList.remove('show');
            });
        });

        function updateTooltipPosition(e) {
            tooltip.style.left = e.pageX + 15 + 'px';
            tooltip.style.top = e.pageY + 15 + 'px';
        }

        // Add click animation
        cells.forEach(cell => {
            cell.addEventListener('click', function() {
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 200);
            });
        });
    </script>
</body>
</html>