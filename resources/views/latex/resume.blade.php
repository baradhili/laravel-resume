\documentclass[11pt, a4paper]{article}

% Basic packages for LuaLaTeX
\usepackage{fontspec}
\usepackage{geometry}
\usepackage{hyperref}
\usepackage{xcolor}
\usepackage{enumitem}

% Simple page layout
\geometry{left=2cm, right=2cm, top=2cm, bottom=2cm}

% Basic colors
\definecolor{heading}{RGB}{0, 0, 0}
\definecolor{meta}{RGB}{100, 100, 100}

% Hyperlink setup
\hypersetup{
    colorlinks=true,
    linkcolor=blue,
    urlcolor=blue,
    pdfborder={0 0 0}
}

% Simple section formatting
\usepackage{titlesec}
\titleformat{\section}{\large\bfseries\color{heading}}{}{0em}{}[\titlerule]

\begin{document}

% ===== HEADER: Name & Contact =====
\begin{center}
    {\Huge\bfseries {{ $name ?? 'No Name' }}}\\[0.5em]
    \small\color{meta}
    @if(!empty($email))\href{mailto:{{ $email }}}{ {{ $email }}}\quad @endif
    @if(!empty($mobile)){{ $mobile }}\quad @endif
    @if(!empty($location)){{ $location }} @endif
    \par
    @if(!empty($position))\textbf{Position:} {{ $position }}\par @endif
    @if(!empty($quote))\textit{``{{ $quote }}''}\par @endif
\end{center}

% ===== DEBUG INFO (remove in production) =====
\section*{Debug Information}
\begin{itemize}
    \item Generated: {{ $generatedAt ?? 'N/A' }}
    \item Filtered: {{ $isFiltered ? 'Yes' : 'No' }}
    @if(!empty($filterKeywords))\item Filter Keywords: {{ $filterKeywords }} @endif
    \item Template: test-resume.tex.blade.php
\end{itemize}

% ===== SUMMARY =====
@if(!empty($summary))
\section{Summary}
{{ $summary }}
@endif

% ===== SKILLS =====
@if(!empty($skills) && is_array($skills))
\section{Skills}
\begin{itemize}
    @foreach($skills as $skill)
        \item \textbf{ {{ $skill['name'] ?? 'Unnamed' }} }: {{ $skill['keywords'] ?? '' }}
    @endforeach
\end{itemize}
@endif

% ===== WORK EXPERIENCE =====
@if(!empty($work) && is_array($work))
\section{Work Experience}
    @foreach($work as $job)
        \subsection*{ {{ $job['position'] ?? 'Unknown Position' }} }
        \textit{ {{ $job['employer'] ?? 'Unknown Employer' }} }
        @if(!empty($job['location'])) | {{ $job['location'] }} @endif
        \hfill {{ $job['startDate'] ?? '' }} -- {{ $job['endDate'] ?? 'Present' }}
        
        @if(!empty($job['summary']))
        \par{{ $job['summary'] }}
        @endif
        
        @if(!empty($job['highlights']) && is_array($job['highlights']))
        \begin{itemize}
            @foreach($job['highlights'] as $highlight)
                \item {!!  $highlight !!}
            @endforeach
        \end{itemize}
        @endif
        \vspace{1em}
    @endforeach
@endif

% ===== EDUCATION =====
@if(!empty($education) && is_array($education))
\section{Education}
    @foreach($education as $edu)
        \subsection*{ {{ $edu['studyType'] ?? '' }}{{ !empty($edu['area']) ? ' in ' . $edu['area'] : '' }} }
        \textit{ {{ $edu['institution'] ?? 'Unknown Institution' }} }
        \hfill {{ $edu['startDate'] ?? '' }}{{ !empty($edu['endDate']) ? ' -- ' . $edu['endDate'] : '' }}
        @if(!empty($edu['score']))\par Score/GPA: {{ $edu['score'] }} @endif
        @if(!empty($edu['courses']) && is_array($edu['courses']))
        \begin{itemize}
            @foreach($edu['courses'] as $course)
                \item {{ $course }}
            @endforeach
        \end{itemize}
        @endif
        \vspace{1em}
    @endforeach
@endif

% ===== VOLUNTEER =====
@if(!empty($volunteer) && is_array($volunteer))
\section{Volunteer Experience}
    @foreach($volunteer as $item)
        \subsection*{ {{ $item['position'] ?? $item['role'] ?? 'Volunteer' }} }
        \textit{ {{ $item['organization'] ?? '' }} }
        @if(!empty($item['summary']))\par{{ $item['summary'] }} @endif
        \vspace{1em}
    @endforeach
@endif

% ===== CERTIFICATIONS =====
@if(!empty($certifications) && is_array($certifications))
\section{Certifications}
\begin{itemize}
    @foreach($certifications as $cert)
        \item \textbf{ {{ $cert['name'] ?? 'Unnamed' }} }
        @if(!empty($cert['issuer']))({{ $cert['issuer'] }}) @endif
        @if(!empty($cert['date']))-- {{ $cert['date'] }} @endif
        @if(!empty($cert['url']))\par\href{ {{ $cert['url'] }} }{View Certificate} @endif
    @endforeach
\end{itemize}
@endif

% ===== PUBLICATIONS =====
@if(!empty($publications) && is_array($publications))
\section{Publications}
\begin{itemize}
    @foreach($publications as $pub)
        \item @if(!empty($pub['url']))\href{ {{ $pub['url'] }} }{ {{ $pub['name'] ?? 'Untitled' }} }@else {{ $pub['name'] ?? 'Untitled' }} @endif
        @if(!empty($pub['publisher']))\textit{ {{ $pub['publisher'] }} } @endif
        @if(!empty($pub['releaseDate']))({{ $pub['releaseDate'] }}) @endif
        @if(!empty($pub['summary']))\par{{ $pub['summary'] }} @endif
    @endforeach
\end{itemize}
@endif

% ===== AWARDS =====
@if(!empty($awards) && is_array($awards))
\section{Awards \& Honors}
\begin{itemize}
    @foreach($awards as $award)
        \item \textbf{ {{ $award['title'] ?? 'Unnamed Award' }} }
        @if(!empty($award['awarder']))-- {{ $award['awarder'] }} @endif
        @if(!empty($award['date']))({{ $award['date'] }})@endif
        @if(!empty($award['summary']))\par{{ $award['summary'] }} @endif
    @endforeach
\end{itemize}
@endif

% ===== LANGUAGES =====
@if(!empty($languages) && is_array($languages))
\section{Languages}
\begin{itemize}
    @foreach($languages as $lang)
        \item {{ $lang['language'] ?? 'Unknown' }}
        @if(!empty($lang['fluency']))\textit{({{ $lang['fluency'] }})} @endif
    @endforeach
\end{itemize}
@endif

% ===== INTERESTS =====
@if(!empty($interests) && is_array($interests))
\section{Interests}
\begin{itemize}
    @foreach($interests as $interest)
        \item \textbf{ {{ $interest['name'] ?? 'Unnamed' }} }
        @if(!empty($interest['keywords']) && is_array($interest['keywords']))
        : {{ implode(', ', $interest['keywords']) }}
        @endif
    @endforeach
\end{itemize}
@endif

% ===== REFERENCES =====
@if(!empty($references) && is_array($references))
\section{References}
\begin{itemize}
    @foreach($references as $ref)
        \item \textbf{ {{ $ref['name'] ?? 'Unnamed' }} }
        @if(!empty($ref['reference']))\par{{ $ref['reference'] }} @endif
    @endforeach
\end{itemize}
@endif

% ===== FILTERED FOOTER NOTE =====
@if(!empty($isFiltered) && !empty($filterKeywords))
\vfill
\begin{center}
    \small\color{meta}This resume was filtered by keywords: {{ $filterKeywords }}\end{center}
@endif


\end{document}