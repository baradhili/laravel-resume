%!TEX TS-program = xelatex
%!TEX encoding = UTF-8 Unicode
% Awesome CV LaTeX Template for CV/Resume - Blade Compatible
% Converted for use with techsemicolon/laravel-php-latex package

%-------------------------------------------------------------------------------
% CONFIGURATIONS
%-------------------------------------------------------------------------------
\documentclass[11pt, a4paper]{awesome-cv}

% Configure page margins with geometry
\geometry{left=1.4cm, top=.8cm, right=1.4cm, bottom=1.8cm, footskip=.5cm}

% Color for highlights - using awesome-red by default
\colorlet{awesome}{awesome-red}

% Set false if you don't want to highlight section with awesome color
\setbool{acvSectionColorHighlight}{true}

% Social information separator
\renewcommand{\acvHeaderSocialSep}{\quad\textbar\quad}

%-------------------------------------------------------------------------------
%	PERSONAL INFORMATION (from Blade variables)
%-------------------------------------------------------------------------------
\name{{ $firstName ?? '' }}{{ $lastName ?? '' }}
\position{{ $position ?? '' }}
\address{{ $location ?? '' }}

@if(!empty($mobile))\mobile{{ $mobile }}@endif
@if(!empty($email))\email{{ $email }}@endif
@if(!empty($homepage))\homepage{{ $homepage }}@endif
@if(!empty($github))\github{{ $github }}@endif
@if(!empty($linkedin))\linkedin{{ $linkedin }}@endif
@if(!empty($twitter))\twitter{{ $twitter }}@endif
@if(!empty($stackoverflowId))\stackoverflow{{ $stackoverflowId }}{{ $stackoverflowName ?? '' }}@endif
@if(!empty($gitlab))\gitlab{{ $gitlab }}@endif
@if(!empty($telegram))\telegram{{ $telegram }}@endif
@if(!empty($medium))\medium{{ $medium }}@endif
@if(!empty($kaggle))\kaggle{{ $kaggle }}@endif
@if(!empty($hackerrank))\hackerrank{{ $hackerrank }}@endif
@if(!empty($reddit))\reddit{{ $reddit }}@endif
@if(!empty($skype))\skype{{ $skype }}@endif
@if(!empty($xHandle))\x{{ $xHandle }}@endif
@if(!empty($googleScholarId))\googlescholar{{ $googleScholarId }}{{ $googleScholarName ?? '' }}@endif
@if(!empty($extraInfo))\extrainfo{{ $extraInfo }}@endif

@if(!empty($quote))\quote{``{{ $quote }}''}@endif

%-------------------------------------------------------------------------------
\begin{document}

% Print the header with personal information
\makecvheader[C]

% Print the footer
\makecvfooter
  {\today}
  {{ $name ?? 'Resume' }}~~~·~~~Résumé
  {\thepage}

%-------------------------------------------------------------------------------
%	SUMMARY SECTION
%-------------------------------------------------------------------------------
@if(!empty($summary))
\cvsection{Summary}
\begin{cventries}
  \cventry
    {}
    {{ $summary }}
    {}
    {}
    {}
\end{cventries}
@endif

%-------------------------------------------------------------------------------
%	WORK EXPERIENCE SECTION
%-------------------------------------------------------------------------------
@if(!empty($work) && is_array($work))
\cvsection{Experience}
\begin{cventries}
  @foreach($work as $job)
    \cventry
      {{ $job['position'] ?? '' }}
      {{ $job['employer'] ?? '' }}
      {{ $job['location'] ?? '' }}
      {{ $job['startDate'] ?? '' }}{{ !empty($job['endDate']) ? ' -- ' . $job['endDate'] : ' -- Present' }}
      @if(!empty($job['summary']))
      {\begin{cvitems}
        \item {{ $job['summary'] }}
      \end{cvitems}}
      @endif
      @if(!empty($job['highlights']) && is_array($job['highlights']))
      {\begin{cvitems}
        @foreach($job['highlights'] as $highlight)
        \item {{ $highlight }}
        @endforeach
      \end{cvitems}}
      @endif
  @endforeach
\end{cventries}
@endif

%-------------------------------------------------------------------------------
%	SKILLS SECTION
%-------------------------------------------------------------------------------
@if(!empty($skills) && is_array($skills))
\cvsection{Skills}
\begin{cvskills}
  @foreach($skills as $skill)
    \cvskill
      {{ $skill['name'] ?? '' }}
      {{ $skill['keywords'] ?? '' }}
  @endforeach
\end{cvskills}
@endif

%-------------------------------------------------------------------------------
%	EDUCATION SECTION
%-------------------------------------------------------------------------------
@if(!empty($education) && is_array($education))
\cvsection{Education}
\begin{cventries}
  @foreach($education as $edu)
    \cventry
      {{ $edu['studyType'] ?? '' }}{{ !empty($edu['area']) ? ' in ' . $edu['area'] : '' }}
      {{ $edu['institution'] ?? '' }}
      {}
      {{ $edu['startDate'] ?? '' }}{{ !empty($edu['endDate']) ? ' -- ' . $edu['endDate'] : '' }}
      @if(!empty($edu['score']))
      {\begin{cvitems}
        \item Score/GPA: {{ $edu['score'] }}
      \end{cvitems}}
      @endif
      @if(!empty($edu['courses']) && is_array($edu['courses']))
      {\begin{cvitems}
        @foreach($edu['courses'] as $course)
        \item {{ $course }}
        @endforeach
      \end{cvitems}}
      @endif
  @endforeach
\end{cventries}
@endif

%-------------------------------------------------------------------------------
%	CERTIFICATIONS SECTION
%-------------------------------------------------------------------------------
@if(!empty($certifications) && is_array($certifications))
\cvsection{Certifications}
\begin{cventries}
  @foreach($certifications as $cert)
    \cventry
      {{ $cert['name'] ?? '' }}
      {{ $cert['issuer'] ?? '' }}
      {}
      {{ $cert['date'] ?? '' }}
      @if(!empty($cert['url']))
      {\begin{cvitems}
        \item \href{{ $cert['url'] }}{View Certificate}
      \end{cvitems}}
      @endif
  @endforeach
\end{cventries}
@endif

%-------------------------------------------------------------------------------
%	PUBLICATIONS SECTION
%-------------------------------------------------------------------------------
@if(!empty($publications) && is_array($publications))
\cvsection{Publications}
\begin{cventries}
  @foreach($publications as $pub)
    \cventry
      {{ $pub['name'] ?? 'Untitled' }}
      {{ $pub['publisher'] ?? '' }}
      {}
      {{ $pub['releaseDate'] ?? '' }}
      @if(!empty($pub['summary']))
      {\begin{cvitems}
        \item {{ $pub['summary'] }}
      \end{cvitems}}
      @endif
      @if(!empty($pub['url']))
      {\begin{cvitems}
        \item \href{{ $pub['url'] }}{Read Online}
      \end{cvitems}}
      @endif
  @endforeach
\end{cventries}
@endif

%-------------------------------------------------------------------------------
%	AWARDS SECTION
%-------------------------------------------------------------------------------
@if(!empty($awards) && is_array($awards))
\cvsection{Awards \& Honors}
\begin{cventries}
  @foreach($awards as $award)
    \cventry
      {{ $award['title'] ?? '' }}
      {{ $award['awarder'] ?? '' }}
      {}
      {{ $award['date'] ?? '' }}
      @if(!empty($award['summary']))
      {\begin{cvitems}
        \item {{ $award['summary'] }}
      \end{cvitems}}
      @endif
  @endforeach
\end{cventries}
@endif

%-------------------------------------------------------------------------------
%	LANGUAGES SECTION
%-------------------------------------------------------------------------------
@if(!empty($languages) && is_array($languages))
\cvsection{Languages}
\begin{cvskills}
  @foreach($languages as $lang)
    \cvskill
      {{ $lang['language'] ?? '' }}
      {{ $lang['fluency'] ?? '' }}
  @endforeach
\end{cvskills}
@endif

%-------------------------------------------------------------------------------
%	INTERESTS SECTION
%-------------------------------------------------------------------------------
@if(!empty($interests) && is_array($interests))
\cvsection{Interests}
\begin{cventries}
  @foreach($interests as $interest)
    \cventry
      {{ $interest['name'] ?? '' }}
      @if(!empty($interest['keywords']) && is_array($interest['keywords']))
      {{ implode(', ', $interest['keywords']) }}
      @endif
      {}
      {}
      {}
  @endforeach
\end{cventries}
@endif

%-------------------------------------------------------------------------------
%	REFERENCES SECTION
%-------------------------------------------------------------------------------
@if(!empty($references) && is_array($references))
\cvsection{References}
\begin{cventries}
  @foreach($references as $ref)
    \cventry
      {{ $ref['name'] ?? '' }}
      @if(!empty($ref['reference']))
      {{ $ref['reference'] }}
      @endif
      {}
      {}
      {}
  @endforeach
\end{cventries}
@endif

%-------------------------------------------------------------------------------
%	FILTERED FOOTER NOTE
%-------------------------------------------------------------------------------
@if(!empty($isFiltered) && !empty($filterKeywords))
\vfill
\begin{center}
  \small\color{graytext}This resume was filtered by keywords: {{ $filterKeywords }}\end{center}
@endif

%-------------------------------------------------------------------------------
\end{document}