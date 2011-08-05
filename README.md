#Once upon a time warp in deep space, the struggle between the nice & the rotten goes on...
Or to be more precise, the struggle between Those Who Want To Get Things Done & Those Who Want To Know How Long It Took Them...

## Premise
When logging time spent on work, I have gotten used to logging what I intendtto do _up front_. That saves me all the hassle of having to try and remember once I am done doing something and helps me stay more focussed on the task at hand.
I have often worked on different projects at the same time and they all tend to have their own place to log time. I wanted a tool that would allow me to track time spent through one web-interface and send it to various different sources. I couldn't really find any tool that did that so using my experience in solving time tracking problems for other companies I decided to write one myself.
This tool is set up in such a manner that it should be fairly straightforward to use and extend by writing connectors to your favourite ticket or tracking tools.

Written with PHP, jQuery, HTML, CSS it tries to use existing project over creating it's own code for all but application-specific behaviour.

So yeah... footprint could be smaller and dependencies less.

## Intended Use
This tool is meant as a web-based interface to let you log your time with as little hassle as possible. The logging syntax takes a tag-based approach, where a tag can be prefixed with a specific symbol to denote a different function for that tag (task, project, context, category, ticket, person, etc). The main difference with most other timers, besides this tag based approach, is that it asks you what you are _going_ to do next, instead of what you have just _done_. It can be asked to remember tags you have already logged with it and you can define tags it should always show.
For the sake of portability (i.e. switching tools), it doesn't allow you to add tasks/tickets the way you would with a todo-app or bug tracking system (you should just use a todo-app or bug tracking system for that). It will, however, allow you to connect it to other sources and take your tasks from there. It comes with connectors for JIRA and Github already on board. It can connect to any product or services that has a web API, all you need to do is write the code or ask me to write it for you ;-)

## About the name...
![Dark Helmet][dark_helmet]

[dark_helmet]: https://github.com/potherca/Dark-Helmet/raw/master/dark_helmet.jpg  "I am your father's brother's nephew's cousin's former roommate!"
